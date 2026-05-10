<?php

namespace App\Filament\Resources\DeliveryOrderResource\Pages;

use App\Filament\Resources\DeliveryOrderResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDeliveryOrder extends ViewRecord
{
    protected static string $resource = DeliveryOrderResource::class;

    protected static string $view = 'filament.resources.delivery-order-resource.pages.view-delivery-order';

    protected function getTitle(): string
    {
        $number = $this->record?->order?->order_number ?? ('#' . $this->record?->id);

        return 'Delivery Summary: ' . $number;
    }

    protected function getActions(): array
    {
        return [Actions\EditAction::make()];
    }

    public function deliveryMetadataRows(): array
    {
        $metadata = $this->record->metadata;
        if (!is_array($metadata) || $metadata === []) {
            return [];
        }

        $rows = [];
        foreach ($metadata as $key => $value) {
            $rows[] = [
                'key' => (string) $key,
                'value' => is_array($value)
                    ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : (string) ($value ?? '—'),
            ];
        }

        return $rows;
    }
}
