<?php

namespace App\Services\Delivery;

use App\Models\Delivery\DeliveryOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CourierSelectorService
{
    public function findForDelivery(DeliveryOrder $deliveryOrder): ?User
    {
        $query = User::query()
            ->couriers()
            ->where('is_active', true);

        $zoneId = $this->resolveGeoZoneId($deliveryOrder);
        if ($zoneId) {
            $query->where(function (Builder $builder) use ($zoneId) {
                $builder->whereNull('geo_zone_id')
                    ->orWhere('geo_zone_id', $zoneId);
            });
        }

        return $query->orderBy('id')->first();
    }

    protected function resolveGeoZoneId(DeliveryOrder $deliveryOrder): ?int
    {
        if ($deliveryOrder->metadata && isset($deliveryOrder->metadata['route']['geo_zone_id'])) {
            return (int) $deliveryOrder->metadata['route']['geo_zone_id'];
        }

        if ($deliveryOrder->metadata && isset($deliveryOrder->metadata['zone_id'])) {
            return (int) $deliveryOrder->metadata['zone_id'];
        }

        return null;
    }
}
