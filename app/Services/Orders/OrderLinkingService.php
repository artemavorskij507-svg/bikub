<?php

namespace App\Services\Orders;

use App\Enums\ServiceType;
use App\Models\Order;

class OrderLinkingService
{
    /**
     * Создать handyman sub-order от исходного заказа (доставка/переезд).
     */
    public function createHandymanSubOrder(
        Order $parent,
        string $handymanServiceType, // HANDYMAN_HOURLY или HANDYMAN_FIXED
        array $attributes = []
    ): Order {
        $attributes = array_merge([
            'service_type' => $handymanServiceType,
            'status' => 'pending_review', // или твой статус
            'currency' => $parent->currency ?? 'NOK',
            'payment_status' => 'pending',
        ], $attributes);

        return $parent->createSubOrder($attributes);
    }

    /**
     * Создать eco sub-order от ремонта/переезда.
     */
    public function createEcoSubOrder(Order $parent, array $attributes = []): Order
    {
        $attributes = array_merge([
            'service_type' => ServiceType::ECO_DISPOSAL->value,
            'status' => 'pending_review',
            'currency' => $parent->currency ?? 'NOK',
            'payment_status' => 'pending',
        ], $attributes);

        return $parent->createSubOrder($attributes);
    }

    /**
     * Создать cleaning sub-order после ремонта/переезда.
     * (Если модуль уборки ещё не реализован — оставь TODO для будущего ServiceType::CLEANING.
     */
    public function createCleaningSubOrder(Order $parent, array $attributes = []): Order
    {
        // TODO: вынести в enum ServiceType::CLEANING_AFTER_REPAIR
        $attributes = array_merge([
            'service_type' => 'cleaning_after_repair', // TODO: вынести в enum
            'status' => 'pending_review',
            'currency' => $parent->currency ?? 'NOK',
            'payment_status' => 'pending',
        ], $attributes);

        return $parent->createSubOrder($attributes);
    }
}
