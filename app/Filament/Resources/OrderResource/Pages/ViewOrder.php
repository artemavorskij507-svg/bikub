<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected static string $view = 'filament.resources.order-resource.pages.view-order';

    protected function getTitle(): string
    {
        $number = $this->record?->order_number ?? ('#' . $this->record?->id);

        return 'Order Summary: ' . $number;
    }

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function trackerUrl(): string
    {
        try {
            return route('account.orders.track', ['order' => $this->record->id]);
        } catch (\Throwable $e) {
            return url('/account/orders/' . $this->record->id . '/track');
        }
    }

    public function getStatusBadgeClass(): string
    {
        return match ((string) ($this->record->status ?? 'pending')) {
            'completed', 'client_confirmed', 'paid_out' => 'bg-emerald-100 text-emerald-800',
            'assigned', 'worker_accepted', 'in_progress', 'worker_en_route', 'arrived' => 'bg-blue-100 text-blue-800',
            'waiting_dispatch', 'payment_pending', 'pending' => 'bg-amber-100 text-amber-800',
            'cancelled', 'disputed', 'failed' => 'bg-rose-100 text-rose-800',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    public function getPaymentBadgeClass(): string
    {
        return match ((string) ($this->record->payment_status ?? 'pending')) {
            'captured' => 'bg-emerald-100 text-emerald-800',
            'reserved', 'authorized' => 'bg-blue-100 text-blue-800',
            'pending' => 'bg-amber-100 text-amber-800',
            'refunded', 'partially_refunded' => 'bg-violet-100 text-violet-800',
            'failed', 'cancelled' => 'bg-rose-100 text-rose-800',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    public function getPriorityBadgeClass(): string
    {
        return in_array((string) ($this->record->priority ?? 'normal'), ['urgent', 'high'], true)
            ? 'bg-rose-100 text-rose-800'
            : 'bg-slate-100 text-slate-700';
    }

    public function metadataRows(): array
    {
        $metadata = $this->record->metadata;
        if (! is_array($metadata) || $metadata === []) {
            return [];
        }

        $rows = [];

        foreach ($metadata as $key => $value) {
            if ($key === 'lifecycle' && is_array($value)) {
                $rows[] = [
                    'key' => 'lifecycle',
                    'value' => $this->lifecycleSummary($value),
                    'expandable' => true,
                    'expanded_value' => $this->formatStructuredValue($value),
                ];
                continue;
            }

            $expandable = is_array($value) || is_object($value);
            $rows[] = [
                'key' => (string) $key,
                'value' => $expandable ? $this->compactStructuredValue($value) : $this->scalarValue($value),
                'expandable' => $expandable,
                'expanded_value' => $expandable ? $this->formatStructuredValue($value) : null,
            ];
        }

        return $rows;
    }

    public function eventRows(): array
    {
        if (! Schema::hasTable('order_events')) {
            return [];
        }

        return $this->record->events()
            ->limit(30)
            ->get(['event_type', 'from_status', 'to_status', 'actor_type', 'actor_id', 'payload', 'created_at'])
            ->map(function ($event): array {
                $actor = $event->actor_type
                    ? class_basename((string) $event->actor_type) . '#' . ($event->actor_id ?? 'n/a')
                    : 'system';

                return [
                    'type' => (string) $event->event_type,
                    'transition' => trim(($event->from_status ?? '—') . ' → ' . ($event->to_status ?? '—')),
                    'actor' => $actor,
                    'at' => optional($event->created_at)->format('Y-m-d H:i:s') ?? '—',
                    'payload' => $this->summarizePayload($event->payload),
                ];
            })
            ->all();
    }

    public function relatedDetailRows(): array
    {
        $order = $this->record->loadMissing(['deliveryOrder', 'movingOrder', 'disposalDetails', 'handymanDetails', 'roadsideDetails']);

        return [
            'delivery' => $order->deliveryOrder,
            'moving' => $order->movingOrder,
            'eco' => $order->disposalDetails,
            'handyman' => $order->handymanDetails,
            'tow' => $order->roadsideDetails,
        ];
    }

    private function lifecycleSummary(array $lifecycle): string
    {
        $count = count($lifecycle);
        if ($count === 0) {
            return '0 transitions';
        }

        $last = end($lifecycle);
        if (! is_array($last)) {
            return $count . ' transitions';
        }

        $from = $last['from'] ?? '—';
        $to = $last['to'] ?? '—';
        $at = $last['changed_at'] ?? '—';

        return $count . " transitions, last: {$from} → {$to} at {$at}";
    }

    private function compactStructuredValue($value): string
    {
        if (is_array($value)) {
            return 'array(' . count($value) . ')';
        }

        if (is_object($value)) {
            return 'object';
        }

        return '—';
    }

    private function formatStructuredValue($value): string
    {
        return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    private function summarizePayload($payload): string
    {
        if (is_array($payload) && $payload !== []) {
            return Str::limit((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 220);
        }

        return '—';
    }

    private function scalarValue($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
