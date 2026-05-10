<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Created = 'created';
    case PaymentPending = 'payment_pending';
    case PaymentReserved = 'payment_reserved';
    case Confirmed = 'confirmed';
    case WaitingDispatch = 'waiting_dispatch';
    case Assigned = 'assigned';
    case WorkerAccepted = 'worker_accepted';
    case WorkerEnRoute = 'worker_en_route';
    case AtPickup = 'at_pickup';
    case PickedUp = 'picked_up';
    case InProgress = 'in_progress';
    case Arrived = 'arrived';
    case Completed = 'completed';
    case ClientConfirmed = 'client_confirmed';
    case PaidOut = 'paid_out';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case Disputed = 'disputed';
    case Failed = 'failed';

    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }

    public static function legacyValues(): array
    {
        return ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];
    }

    public static function allAcceptedValues(): array
    {
        return array_values(array_unique([...self::values(), ...self::legacyValues()]));
    }

    public static function normalize(string $status): string
    {
        return match ($status) {
            'pending' => self::WaitingDispatch->value,
            default => $status,
        };
    }
}
