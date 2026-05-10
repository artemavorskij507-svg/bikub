<?php

namespace App\Enums;

enum DeliveryTrackingStatus: string
{
    case PENDING = 'pending';
    case ASSIGNED = 'assigned';
    case PICKED_UP = 'picked_up';
    case IN_TRANSIT = 'in_transit';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидается',
            self::ASSIGNED => 'Назначено',
            self::PICKED_UP => 'Забрано',
            self::IN_TRANSIT => 'В дороге',
            self::DELIVERED => 'Доставлено',
            self::CANCELLED => 'Отменено',
        };
    }
}
