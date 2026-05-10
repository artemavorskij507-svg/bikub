<?php

namespace App\Services\EcoDisposal;

use App\Models\DisposalOrderDetails;
use App\Models\DisposalPartner;
use App\Models\EcoTeam;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Illuminate\Support\Facades\Schema;

class EcoDisposalDispatchService
{
    private const STATUS_SCHEDULED   = 'SCHEDULED';
    private const STATUS_ASSIGNED    = 'ASSIGNED';
    private const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    private const STATUS_AT_PARTNER  = 'AT_PARTNER';
    private const STATUS_COMPLETED   = 'COMPLETED';
    private const STATUS_CANCELLED   = 'CANCELLED';

    public function assignTeamToOrder(Order $order, EcoTeam $team, ?User $dispatcher = null): Order
    {
        $this->assertEcoOrder($order);
        return DB::transaction(function () use ($order, $team, $dispatcher) {
            $details = $this->getOrCreateDetails($order);
            $current = $this->normalizeStatus($details->eco_status);
            if (!in_array($current, [null, self::STATUS_SCHEDULED, 'PENDING', 'PENDING_ASSIGNMENT'], true)) {
                throw new InvalidArgumentException("Назначение команды не допускается из статуса {$current}");
            }
            $details->eco_team_id = $team->id;
            $this->setEcoStatus($details, self::STATUS_ASSIGNED);
            $details->save();

            // Зафиксируем назначение в metadata заказа
            $meta = $order->metadata ?? [];
            $meta['eco']['assigned_team_id'] = $team->id;
            $meta['eco']['assigned_by'] = $dispatcher?->id;
            $order->metadata = $meta;
            // Бизнес-статус: подтвержден/назначен
            $order->status = 'confirmed';
            $order->save();

            Log::info('Eco team assigned', ['order_id' => $order->id, 'team_id' => $team->id, 'by' => $dispatcher?->id]);
            event(new \App\Events\EcoDisposal\EcoDisposalTeamAssigned($order, $team, $dispatcher));
            event(new \App\Events\EcoDisposal\EcoDisposalStatusChanged($order, $current, self::STATUS_ASSIGNED, $dispatcher));
            return $order->fresh();
        });
    }

    public function markInProgress(Order $order, ?User $dispatcher = null): Order
    {
        $this->assertEcoOrder($order);
        return DB::transaction(function () use ($order, $dispatcher) {
            $details = $this->getOrCreateDetails($order);
            $current = $this->normalizeStatus($details->eco_status);
            if ($current !== self::STATUS_ASSIGNED) {
                throw new InvalidArgumentException("Переход в IN_PROGRESS допустим только из ASSIGNED, текущий: {$current}");
            }
            $this->setEcoStatus($details, self::STATUS_IN_PROGRESS);
            $details->save();

            $order->status = 'in_progress';
            $order->save();

            Log::info('Eco order in progress', ['order_id' => $order->id, 'by' => $dispatcher?->id]);
            event(new \App\Events\EcoDisposal\EcoDisposalStatusChanged($order, $current, self::STATUS_IN_PROGRESS, $dispatcher));
            return $order->fresh();
        });
    }

    public function markAtPartner(Order $order, DisposalPartner $partner, ?User $dispatcher = null): Order
    {
        $this->assertEcoOrder($order);
        return DB::transaction(function () use ($order, $partner, $dispatcher) {
            $details = $this->getOrCreateDetails($order);
            $current = $this->normalizeStatus($details->eco_status);
            if ($current !== self::STATUS_IN_PROGRESS) {
                throw new InvalidArgumentException("Переход в AT_PARTNER допустим только из IN_PROGRESS, текущий: {$current}");
            }
            $details->eco_partner_id = $partner->id;
            $this->setEcoStatus($details, self::STATUS_AT_PARTNER);
            $details->save();

            // Бизнес-статус заказа оставляем in_progress
            if ($order->status !== 'in_progress') {
                $order->status = 'in_progress';
                $order->save();
            }

            Log::info('Eco order at partner', ['order_id' => $order->id, 'partner_id' => $partner->id, 'by' => $dispatcher?->id]);
            event(new \App\Events\EcoDisposal\EcoDisposalPartnerAssigned($order, $partner, $dispatcher));
            event(new \App\Events\EcoDisposal\EcoDisposalStatusChanged($order, $current, self::STATUS_AT_PARTNER, $dispatcher));
            return $order->fresh();
        });
    }

    public function markCompleted(Order $order, ?User $dispatcher = null): Order
    {
        $this->assertEcoOrder($order);
        return DB::transaction(function () use ($order, $dispatcher) {
            $details = $this->getOrCreateDetails($order);
            $current = $this->normalizeStatus($details->eco_status);
            if (!in_array($current, [self::STATUS_AT_PARTNER, self::STATUS_IN_PROGRESS], true)) {
                throw new InvalidArgumentException("Завершение допустимо из AT_PARTNER или IN_PROGRESS, текущий: {$current}");
            }
            $this->setEcoStatus($details, self::STATUS_COMPLETED);
            $details->save();

            $order->status = 'completed';
            $order->completed_at = now();
            $order->save();

            Log::info('Eco order completed', ['order_id' => $order->id, 'by' => $dispatcher?->id]);
            // TODO: trigger EcoCertificate generation
            event(new \App\Events\EcoDisposal\EcoDisposalStatusChanged($order, $current, self::STATUS_COMPLETED, $dispatcher));

            // Trigger certificate issue (soft-fail, keep main flow)
            try {
                app(\App\Services\EcoDisposal\EcoCertificateService::class)->issueForOrder($order);
            } catch (\Throwable $e) {
                Log::error('EcoCertificate issue failed', ['order_id' => $order->id, 'err' => $e->getMessage()]);
            }
            return $order->fresh();
        });
    }

    public function cancelEcoOrder(Order $order, string $reason, ?User $dispatcher = null): Order
    {
        $this->assertEcoOrder($order);
        return DB::transaction(function () use ($order, $reason, $dispatcher) {
            $details = $this->getOrCreateDetails($order);
            $current = $this->normalizeStatus($details->eco_status);
            if (in_array($current, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true)) {
                throw new InvalidArgumentException("Отмена недопустима из финального состояния: {$current}");
            }
            $this->setEcoStatus($details, self::STATUS_CANCELLED);
            $details->save();

            $order->status = 'cancelled';
            $meta = $order->metadata ?? [];
            $meta['eco']['cancel_reason'] = $reason;
            $meta['eco']['cancelled_by'] = $dispatcher?->id;
            $order->metadata = $meta;
            if (Schema()->hasColumn($order->getTable(), 'cancellation_reason')) {
                $order->cancellation_reason = $reason;
            }
            $order->save();

            Log::warning('Eco order cancelled', ['order_id' => $order->id, 'by' => $dispatcher?->id, 'reason' => $reason]);
            event(new \App\Events\EcoDisposal\EcoDisposalStatusChanged($order, $current, self::STATUS_CANCELLED, $dispatcher));
            return $order->fresh();
        });
    }

    protected function getOrCreateDetails(Order $order): DisposalOrderDetails
    {
        if (!$order->disposalDetails) {
            $order->load('disposalDetails');
        }
        if ($order->disposalDetails) {
            return $order->disposalDetails;
        }
        return DisposalOrderDetails::create([
            'order_id' => $order->id,
            'items' => [],
            'eco_status' => self::STATUS_SCHEDULED,
        ]);
    }

    private function assertEcoOrder(Order $order): void
    {
        if (!$order->isEcoDisposal()) {
            throw new InvalidArgumentException('Операции диспетчеризации допустимы только для ЭКО-заказов');
        }
    }

    private function normalizeStatus(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }
        $up = strtoupper($status);
        // normalize legacy/lowercase values
        return match ($up) {
            'SCHEDULED', 'PENDING', 'PENDING_ASSIGNMENT' => self::STATUS_SCHEDULED,
            'ASSIGNED' => self::STATUS_ASSIGNED,
            'IN_PROGRESS' => self::STATUS_IN_PROGRESS,
            'AT_PARTNER' => self::STATUS_AT_PARTNER,
            'COMPLETED' => self::STATUS_COMPLETED,
            'CANCELLED', 'CANCELED' => self::STATUS_CANCELLED,
            default => $up,
        };
    }

    private function setEcoStatus(DisposalOrderDetails $details, string $to): void
    {
        $details->eco_status = $to;
    }
}

<?php

namespace App\Services\EcoDisposal;

use App\Models\DisposalOrderDetails;
use App\Models\DisposalPartner;
use App\Models\EcoTeam;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class EcoDisposalDispatchService
{
    /**
     * Assign an eco team to an ECO_DISPOSAL order.
     */
    public function assignTeamToOrder(Order $order, EcoTeam $team, ?User $dispatcher = null): Order
    {
        $this->ensureEcoOrder($order);

        return DB::transaction(function () use ($order, $team, $dispatcher) {
            /** @var DisposalOrderDetails $details */
            $details = $order->disposalDetails()->firstOrCreate([
                'order_id' => $order->id,
            ]);

            $details->eco_team_id = $team->id;
            // If order was pending, mark as confirmed (scheduled/assigned)
            if ($order->status === 'pending') {
                $order->status = 'confirmed';
            }

            // eco_status: ASSIGNED when team is set
            $details->eco_status = 'ASSIGNED';

            $metadata = $order->metadata ?? [];
            $metadata['eco'] = array_merge($metadata['eco'] ?? [], [
                'team_assigned_at' => now()->toISOString(),
                'team_assigned_by' => $dispatcher?->id,
                'team_id' => $team->id,
            ]);
            $order->metadata = $metadata;

            $order->save();
            $details->save();

            Log::info('Eco team assigned to eco disposal order', [
                'order_id' => $order->id,
                'eco_team_id' => $team->id,
                'dispatcher_id' => $dispatcher?->id,
            ]);

            return $order->fresh('disposalDetails');
        });
    }

    /**
     * Mark ECO order as in progress.
     */
    public function markInProgress(Order $order, ?User $dispatcher = null): Order
    {
        $this->ensureEcoOrder($order);

        return DB::transaction(function () use ($order, $dispatcher) {
            /** @var DisposalOrderDetails $details */
            $details = $order->disposalDetails()->firstOrCreate([
                'order_id' => $order->id,
            ]);

            if (!in_array($order->status, ['confirmed', 'in_progress'], true)) {
                throw new InvalidArgumentException('Заказ не может быть переведен в IN_PROGRESS из текущего статуса.');
            }

            $order->status = 'in_progress';
            if (!$order->started_at) {
                $order->started_at = now();
            }

            $details->eco_status = 'IN_PROGRESS';

            $metadata = $order->metadata ?? [];
            $metadata['eco'] = array_merge($metadata['eco'] ?? [], [
                'in_progress_at' => now()->toISOString(),
                'in_progress_set_by' => $dispatcher?->id,
            ]);
            $order->metadata = $metadata;

            $order->save();
            $details->save();

            Log::info('Eco disposal order marked IN_PROGRESS', [
                'order_id' => $order->id,
                'dispatcher_id' => $dispatcher?->id,
            ]);

            return $order->fresh('disposalDetails');
        });
    }

    /**
     * Mark ECO order as at partner and set partner.
     */
    public function markAtPartner(Order $order, DisposalPartner $partner, ?User $dispatcher = null): Order
    {
        $this->ensureEcoOrder($order);

        return DB::transaction(function () use ($order, $partner, $dispatcher) {
            /** @var DisposalOrderDetails $details */
            $details = $order->disposalDetails()->firstOrCreate([
                'order_id' => $order->id,
            ]);

            if (!in_array($order->status, ['in_progress', 'confirmed'], true)) {
                throw new InvalidArgumentException('Заказ не может быть отправлен к партнёру из текущего статуса.');
            }

            // Keep global status as in_progress; use eco_status for AT_PARTNER
            $order->status = 'in_progress';
            $details->eco_partner_id = $partner->id;
            $details->eco_status = 'AT_PARTNER';

            $metadata = $order->metadata ?? [];
            $metadata['eco'] = array_merge($metadata['eco'] ?? [], [
                'at_partner_at' => now()->toISOString(),
                'partner_id' => $partner->id,
                'at_partner_set_by' => $dispatcher?->id,
            ]);
            $order->metadata = $metadata;

            $order->save();
            $details->save();

            Log::info('Eco disposal order marked AT_PARTNER', [
                'order_id' => $order->id,
                'eco_partner_id' => $partner->id,
                'dispatcher_id' => $dispatcher?->id,
            ]);

            return $order->fresh('disposalDetails');
        });
    }

    /**
     * Mark ECO order as completed.
     */
    public function markCompleted(Order $order, ?User $dispatcher = null): Order
    {
        $this->ensureEcoOrder($order);

        if (! $order->canBeMarkedAsCompletedEcoDisposal()) {
            throw new InvalidArgumentException('Заказ не может быть завершён из текущего статуса.');
        }

        return DB::transaction(function () use ($order, $dispatcher) {
            /** @var DisposalOrderDetails $details */
            $details = $order->disposalDetails()->firstOrCreate([
                'order_id' => $order->id,
            ]);

            $order->status = 'completed';
            $order->completed_at = now();
            $details->eco_status = 'COMPLETED';

            $metadata = $order->metadata ?? [];
            $metadata['eco'] = array_merge($metadata['eco'] ?? [], [
                'completed_at' => now()->toISOString(),
                'completed_by' => $dispatcher?->id,
            ]);
            $order->metadata = $metadata;

            $order->save();
            $details->save();

            Log::info('Eco disposal order marked COMPLETED', [
                'order_id' => $order->id,
                'dispatcher_id' => $dispatcher?->id,
            ]);

            return $order->fresh('disposalDetails');
        });
    }

    /**
     * Cancel ECO order.
     */
    public function cancelEcoOrder(Order $order, string $reason, ?User $dispatcher = null): Order
    {
        $this->ensureEcoOrder($order);

        if (!in_array($order->status, ['pending', 'confirmed', 'in_progress'], true)) {
            throw new InvalidArgumentException('Заказ не может быть отменен в текущем статусе.');
        }

        return DB::transaction(function () use ($order, $reason, $dispatcher) {
            /** @var DisposalOrderDetails $details */
            $details = $order->disposalDetails()->firstOrCreate([
                'order_id' => $order->id,
            ]);

            $order->status = 'cancelled';
            $details->eco_status = 'CANCELLED';

            $metadata = $order->metadata ?? [];
            $metadata['eco'] = array_merge($metadata['eco'] ?? [], [
                'cancelled_at' => now()->toISOString(),
                'cancelled_by' => $dispatcher?->id,
                'cancellation_reason' => $reason,
            ]);
            $order->metadata = $metadata;

            $order->save();
            $details->save();

            Log::info('Eco disposal order CANCELLED', [
                'order_id' => $order->id,
                'dispatcher_id' => $dispatcher?->id,
                'reason' => $reason,
            ]);

            return $order->fresh('disposalDetails');
        });
    }

    protected function ensureEcoOrder(Order $order): void
    {
        if (! $order->isEcoDisposal()) {
            throw new InvalidArgumentException('Операция доступна только для ЭКО-заказов.');
        }
    }
}


