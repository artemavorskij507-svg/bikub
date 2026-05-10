<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate unique order number if empty
        if (empty($data['order_number'])) {
            $data['order_number'] = method_exists(Order::class, 'generateOrderNumber')
                ? Order::generateOrderNumber()
                : ('ORD-'.date('Ymd-His').'-'.random_int(1000, 9999));
        }

        // Normalize JSON-like fields from textarea
        foreach (['location', 'metadata'] as $jsonField) {
            if (array_key_exists($jsonField, $data)) {
                $value = $data[$jsonField];
                if (is_string($value)) {
                    $trim = trim($value);
                    if ($trim === '') {
                        $data[$jsonField] = null;
                    } else {
                        $decoded = json_decode($trim, true);
                        $data[$jsonField] = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                    }
                }
            }
        }

        // Defaults
        $data['currency'] = $data['currency'] ?? 'NOK';
        $data['payment_status'] = $data['payment_status'] ?? 'pending';

        // Cast numeric
        if (isset($data['total_amount']) && $data['total_amount'] !== '') {
            $data['total_amount'] = (float) $data['total_amount'];
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Tasks will be generated automatically after payment via OrderPaid event
        // No need to generate tasks here - they should only be created after payment confirmation
    }
}
