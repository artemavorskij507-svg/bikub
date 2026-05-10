<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\Partner;
use App\Models\RoadHelperProfile;
use App\Models\RoadsideAssistanceDetail;
use App\Models\RoadsideEmergency;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoadsideDispatchBoard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-lightning-bolt';

    protected static string $view = 'filament.pages.roadside-dispatch-board';

    protected static ?string $navigationLabel = 'Roadside Dispatch';

    protected static ?string $navigationGroup = 'Roadside & Tow';

    protected static ?int $navigationSort = 602;

    public ?int $selectedOrderId = null;

    public string $statusFilter = 'active';

    public ?string $serviceTypeFilter = null;

    public ?int $zoneFilter = null;

    public ?int $assignExecutorId = null;

    public ?int $assignPartnerId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'operator', 'dispatcher']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getTitle(): string
    {
        return 'Roadside Dispatch Board';
    }

    protected function getRoadsideQuery(): Builder
    {
        $query = Order::query()
            ->where(function ($q) {
                $q->whereHas('roadsideDetails')
                    ->orWhereHas('roadsideEmergency')
                    ->orWhereHas('vehicleInspection')
                    ->orWhereHas('orderItems.serviceType', function ($sq) {
                        $sq->where(function ($q) {
                            $q->whereIn('code', ['roadside_assistance', 'vehicle_inspection', 'vehicle_transport'])
                                ->orWhereIn('category', ['roadside_assistance', 'vehicle_inspection', 'vehicle_transport']);
                        });
                    });
            })
            ->with([
                'user',
                'assignedUser',
                'roadsideDetails.partner',
                'roadsideEmergency',
                'vehicleInspection',
                'orderItems.serviceType',
                'geoZone',
            ]);

        // Status filter
        if ($this->statusFilter === 'active') {
            $query->whereIn('status', ['pending', 'assigned', 'in_progress']);
        } elseif ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        // Service type filter
        if ($this->serviceTypeFilter) {
            $query->whereHas('orderItems.serviceType', function ($q) {
                $q->where('code', $this->serviceTypeFilter)
                    ->orWhere('category', $this->serviceTypeFilter);
            });
        }

        // Zone filter
        if ($this->zoneFilter) {
            $query->where('geo_zone_id', $this->zoneFilter);
        }

        return $query
            ->orderByRaw("CASE status 
                WHEN 'pending' THEN 1 
                WHEN 'assigned' THEN 2 
                WHEN 'in_progress' THEN 3 
                ELSE 4 
            END")
            ->orderBy('created_at', 'asc');
    }

    public function getActiveOrdersProperty()
    {
        return $this->getRoadsideQuery()->get();
    }

    public function getActiveRoadsideJobsProperty()
    {
        return $this->getActiveRoadsideJobs();
    }

    public function getAvailableHelpersProperty()
    {
        return $this->getAvailableHelpers();
    }

    public function getAvailablePartnersProperty()
    {
        return $this->getAvailablePartners();
    }

    /**
     * Get statistics for dashboard.
     */
    public function getStatsProperty()
    {
        $activeJobs = RoadsideEmergency::active()->get();

        return [
            'new_jobs' => RoadsideEmergency::new()->count(),
            'active_jobs' => $activeJobs->count(),
            'awaiting_assign' => RoadsideEmergency::awaitingAssignment()->count(),
            'overdue_assign' => $activeJobs->filter(fn ($job) => $job->is_overdue_assignment)->count(),
            'overdue_arrival' => $activeJobs->filter(fn ($job) => $job->is_overdue_arrival)->count(),
        ];
    }

    public function getSelectedOrderProperty()
    {
        if (! $this->selectedOrderId) {
            return null;
        }

        return Order::with([
            'user',
            'assignedUser',
            'roadsideDetails.partner',
            'roadsideEmergency',
            'vehicleInspection',
            'orderItems.serviceType',
            'geoZone',
        ])->find($this->selectedOrderId);
    }

    public function selectOrder(int $orderId): void
    {
        $this->selectedOrderId = $orderId;
        $this->reset(['assignExecutorId', 'assignPartnerId']);
    }

    public function assignExecutor(): void
    {
        if (! $this->selectedOrderId || ! $this->assignExecutorId) {
            return;
        }

        try {
            DB::beginTransaction();

            $order = Order::findOrFail($this->selectedOrderId);
            $order->update([
                'assigned_to' => $this->assignExecutorId,
                'status' => $order->status === 'pending' ? 'assigned' : $order->status,
                'metadata' => array_merge($order->metadata ?? [], [
                    'assigned_at' => now()->toISOString(),
                    'assigned_by' => auth()->id(),
                ]),
            ]);

            Log::info('Executor assigned to roadside order', [
                'order_id' => $order->id,
                'executor_id' => $this->assignExecutorId,
                'assigned_by' => auth()->id(),
            ]);

            DB::commit();

            \Filament\Notifications\Notification::make()
                ->title('Исполнитель назначен')
                ->success()
                ->send();

            $this->reset(['assignExecutorId']);
            $this->selectedOrderId = null; // Refresh
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign executor', [
                'order_id' => $this->selectedOrderId,
                'error' => $e->getMessage(),
            ]);

            \Filament\Notifications\Notification::make()
                ->title('Ошибка при назначении')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function assignPartner(): void
    {
        if (! $this->selectedOrderId || ! $this->assignPartnerId) {
            return;
        }

        try {
            DB::beginTransaction();

            $order = Order::findOrFail($this->selectedOrderId);
            $detail = $order->roadsideDetails;

            if (! $detail) {
                $detail = RoadsideAssistanceDetail::create([
                    'order_id' => $order->id,
                ]);
            }

            $detail->update([
                'partner_id' => $this->assignPartnerId,
            ]);

            Log::info('Partner assigned to roadside order', [
                'order_id' => $order->id,
                'partner_id' => $this->assignPartnerId,
                'assigned_by' => auth()->id(),
            ]);

            DB::commit();

            \Filament\Notifications\Notification::make()
                ->title('Партнёр назначен')
                ->success()
                ->send();

            $this->reset(['assignPartnerId']);
            $this->selectedOrderId = null; // Refresh
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign partner', [
                'order_id' => $this->selectedOrderId,
                'error' => $e->getMessage(),
            ]);

            \Filament\Notifications\Notification::make()
                ->title('Ошибка при назначении')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function updateStatus(string $action, ?string $reason = null): void
    {
        if (! $this->selectedOrderId) {
            return;
        }

        try {
            DB::beginTransaction();

            $order = Order::findOrFail($this->selectedOrderId);
            $oldStatus = $order->status;
            $metadata = $order->metadata ?? [];
            $notes = $order->notes ?? '';

            switch ($action) {
                case 'accept':
                    if (! in_array($order->status, ['pending', 'confirmed'])) {
                        throw new \Exception('Заказ не может быть принят в текущем статусе');
                    }
                    $order->status = 'assigned';
                    $metadata['assigned_at'] = now()->toISOString();
                    $notes .= "\n".now()->format('Y-m-d H:i:s').' - Заказ принят диспетчером';
                    break;

                case 'start_travel':
                    if (! in_array($order->status, ['assigned', 'confirmed'])) {
                        throw new \Exception('Заказ не может быть в пути в текущем статусе');
                    }
                    $order->status = 'in_progress';
                    if (! $order->started_at) {
                        $order->started_at = now();
                    }
                    $metadata['travel_started_at'] = now()->toISOString();
                    $notes .= "\n".now()->format('Y-m-d H:i:s').' - Исполнитель выехал к клиенту';
                    break;

                case 'arrived':
                    if ($order->status !== 'in_progress') {
                        throw new \Exception('Заказ должен быть в процессе выполнения');
                    }
                    $metadata['arrived_at'] = now()->toISOString();
                    $notes .= "\n".now()->format('Y-m-d H:i:s').' - Исполнитель прибыл на место';
                    break;

                case 'start_job':
                    if ($order->status !== 'in_progress') {
                        throw new \Exception('Заказ должен быть в процессе выполнения');
                    }
                    $metadata['job_started_at'] = now()->toISOString();
                    $notes .= "\n".now()->format('Y-m-d H:i:s').' - Исполнитель начал работу';
                    break;

                case 'finish_job':
                    if (! in_array($order->status, ['in_progress', 'assigned'])) {
                        throw new \Exception('Заказ не может быть завершен в текущем статусе');
                    }
                    $order->status = 'completed';
                    $order->completed_at = now();
                    $metadata['completed_at'] = now()->toISOString();
                    $notes .= "\n".now()->format('Y-m-d H:i:s').' - Заказ выполнен';
                    break;

                case 'cancel':
                    if (! in_array($order->status, ['assigned', 'in_progress', 'pending', 'confirmed'])) {
                        throw new \Exception('Заказ не может быть отменен в текущем статусе');
                    }
                    if (empty($reason)) {
                        throw new \Exception('Необходимо указать причину отмены');
                    }
                    $order->status = 'cancelled';
                    $metadata['cancelled_at'] = now()->toISOString();
                    $metadata['cancelled_by'] = auth()->id();
                    $metadata['cancellation_reason'] = $reason;
                    $notes .= "\n".now()->format('Y-m-d H:i:s').' - Заказ отменен диспетчером. Причина: '.$reason;
                    break;
            }

            $order->metadata = $metadata;
            if (! empty($notes)) {
                $order->notes = trim($notes);
            }
            $order->save();

            Log::info('Roadside order status updated', [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $order->status,
                'action' => $action,
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            \Filament\Notifications\Notification::make()
                ->title('Статус обновлён')
                ->success()
                ->send();

            $this->selectedOrderId = null; // Refresh
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update order status', [
                'order_id' => $this->selectedOrderId,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            \Filament\Notifications\Notification::make()
                ->title('Ошибка')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Get active roadside emergencies (jobs).
     */
    protected function getActiveRoadsideJobs()
    {
        return RoadsideEmergency::query()
            ->active()
            ->with([
                'order',
                'order.user',
                'order.assignedUser',
                'helper.user',
                'partner',
                'customer',
            ])
            ->latest('created_at')
            ->limit(50)
            ->get();
    }

    /**
     * Get available helpers.
     */
    protected function getAvailableHelpers()
    {
        return RoadHelperProfile::query()
            ->available()
            ->with('user')
            ->orderBy('current_status')
            ->limit(50)
            ->get();
    }

    /**
     * Get available partners.
     */
    protected function getAvailablePartners()
    {
        return Partner::query()
            ->roadside()
            ->available()
            ->orderBy('name')
            ->limit(50)
            ->get();
    }

    public function getExecutorsProperty()
    {
        return User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['roadside_assist', 'executor', 'eco_executor']);
        })
            ->get()
            ->mapWithKeys(fn ($user) => [$user->id => $user->name.' ('.$user->email.')']);
    }

    public function getPartnersProperty()
    {
        return Partner::roadside()
            ->available()
            ->get()
            ->mapWithKeys(fn ($partner) => [$partner->id => $partner->name]);
    }

    public function getServiceTypeLabel(string $code): string
    {
        return match ($code) {
            'roadside_assistance' => 'Помощь на дороге',
            'vehicle_transport' => 'Эвакуация',
            'vehicle_inspection' => 'Осмотр авто',
            default => $code,
        };
    }

    public function getWaitingTime(Order $order): string
    {
        $startTime = $order->metadata['assigned_at'] ?? $order->created_at;
        if (is_string($startTime)) {
            try {
                $startTime = Carbon::parse($startTime);
            } catch (\Exception $e) {
                \Log::warning('Failed to parse startTime in RoadsideDispatchBoard', [
                    'order_id' => $order->id,
                    'startTime' => $startTime,
                    'error' => $e->getMessage(),
                ]);
                $startTime = $order->created_at;
            }
        }

        $minutes = $startTime->diffInMinutes(now());

        if ($minutes < 60) {
            return $minutes.' мин';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return $hours.' ч '.$mins.' мин';
    }
}
