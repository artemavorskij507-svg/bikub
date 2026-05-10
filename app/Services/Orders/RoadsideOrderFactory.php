<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RoadsideAssistanceDetail;
use App\Models\RoadsidePreset;
use App\Models\ServiceType;
use Illuminate\Support\Facades\DB;

class RoadsideOrderFactory
{
    /**
     * Create a child roadside order for a parent order.
     *
     * @param  Order  $parent  The parent order
     * @param  array  $data  Roadside order data
     */
    public function createChildRoadsideOrderFor(Order $parent, array $data): Order
    {
        return DB::transaction(function () use ($parent, $data) {
            // Find or create service type
            $serviceType = $this->findOrCreateServiceType($data['service_type']);

            // Find preset if provided
            $preset = null;
            if (isset($data['preset_code'])) {
                $preset = RoadsidePreset::where('code', $data['preset_code'])->first();
            }

            // Create the child order
            $order = Order::create([
                'user_id' => $parent->user_id,
                'parent_order_id' => $parent->id,
                'status' => 'pending',
                'priority' => $parent->priority ?? 'normal',
                'geo_zone_id' => $parent->geo_zone_id,
                'address_id' => $parent->address_id,
                'currency' => $parent->currency ?? 'NOK',
                'payment_status' => 'pending',
                'total_amount' => $preset ? $preset->base_price : 0,
                'estimated_total' => $preset ? (int) ($preset->base_price * 100) : 0,
                'notes' => $data['note'] ?? null,
                'location' => $this->buildLocation($data),
                'metadata' => [
                    'created_via' => 'roadside_suborder',
                    'parent_order_id' => $parent->id,
                    'parent_order_number' => $parent->order_number,
                    'service_type' => $data['service_type'],
                    'preset_code' => $data['preset_code'] ?? null,
                ],
            ]);

            // Create order item
            OrderItem::create([
                'order_id' => $order->id,
                'service_type_id' => $serviceType->id,
                'title' => $preset ? $preset->label : $this->getServiceTypeLabel($data['service_type']),
                'price' => $preset ? $preset->base_price : 0,
                'quantity' => 1,
            ]);

            // Create roadside assistance detail
            RoadsideAssistanceDetail::create([
                'order_id' => $order->id,
                'subtype' => $data['preset_code'] ?? null,
                'incident_address' => $data['incident_address'] ?? null,
                'incident_lat' => $data['incident_lat'] ?? null,
                'incident_lng' => $data['incident_lng'] ?? null,
                'vehicle_make' => $data['vehicle_make'] ?? null,
                'vehicle_model' => $data['vehicle_model'] ?? null,
                'vehicle_plate' => $data['vehicle_plate'] ?? null,
                'vehicle_color' => $data['vehicle_color'] ?? null,
                'extra' => [
                    'note' => $data['note'] ?? null,
                    'parent_source' => $this->getParentSource($parent),
                ],
            ]);

            return $order->load(['roadsideDetails', 'orderItems.serviceType']);
        });
    }

    /**
     * Find or create service type by code.
     */
    protected function findOrCreateServiceType(string $serviceTypeCode): ServiceType
    {
        $serviceType = ServiceType::where('code', $serviceTypeCode)->first();

        if (! $serviceType) {
            // Create basic service type if doesn't exist
            $serviceType = ServiceType::create([
                'code' => $serviceTypeCode,
                'name' => $this->getServiceTypeLabel($serviceTypeCode),
                'category' => 'roadside_assistance',
                'is_active' => true,
            ]);
        }

        return $serviceType;
    }

    /**
     * Get human-readable label for service type.
     */
    protected function getServiceTypeLabel(string $code): string
    {
        $labels = [
            'roadside_assistance' => 'Помощь на дороге',
            'vehicle_transport' => 'Эвакуация',
            'vehicle_inspection' => 'Осмотр авто',
        ];

        return $labels[$code] ?? ucfirst(str_replace('_', ' ', $code));
    }

    /**
     * Build location array from data.
     */
    protected function buildLocation(array $data): ?array
    {
        if (isset($data['incident_lat']) && isset($data['incident_lng'])) {
            return [
                'lat' => $data['incident_lat'],
                'lng' => $data['incident_lng'],
                'address' => $data['incident_address'] ?? null,
            ];
        }

        return null;
    }

    /**
     * Get parent order source category.
     */
    protected function getParentSource(Order $parent): string
    {
        // Try to determine source from service type or metadata
        $serviceType = $parent->orderItems->first()?->serviceType;

        if ($serviceType) {
            $code = $serviceType->code ?? '';
            $category = $serviceType->category ?? '';

            if (str_contains($code, 'relocation') || str_contains($category, 'relocation')) {
                return 'relocation';
            }
            if (str_contains($code, 'eco') || str_contains($code, 'disposal')) {
                return 'eco';
            }
            if (str_contains($code, 'handyman') || str_contains($code, 'master')) {
                return 'handyman';
            }
            if (str_contains($code, 'errand') || str_contains($code, 'custom')) {
                return 'errand';
            }
        }

        return 'unknown';
    }
}
