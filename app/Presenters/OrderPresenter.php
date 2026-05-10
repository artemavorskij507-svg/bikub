<?php

namespace App\Presenters;

use App\Enums\ServiceType;
use App\Models\Order;
use Illuminate\Support\Facades\Schema;

class OrderPresenter
{
    public static function forAccount(Order $order): array
    {
        [$title, $subtitle, $icon, $color] = self::resolveVisuals($order);

        [$statusLabel, $statusColor] = self::resolveStatus($order->status);

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'title' => $title,
            'subtitle' => $subtitle,
            'icon' => $icon,
            'color' => $color,
            'status_label' => $statusLabel,
            'status_color' => $statusColor,
            'status_key' => $order->status,
            'payment_status' => (string) ($order->payment_status ?? 'pending'),
            'payment_label' => self::resolvePaymentStatusLabel((string) ($order->payment_status ?? 'pending')),
            'payment_color' => self::resolvePaymentStatusColor((string) ($order->payment_status ?? 'pending')),
            'created_at' => $order->created_at,
            'scheduled_at' => $order->scheduled_at,
            'scenario_key' => self::resolveScenarioKey($order),
            'service_label' => self::resolveServiceLabel($order),
            'price_value' => self::resolvePrice($order),
            'currency' => (string) ($order->currency ?? 'NOK'),
            'has_parent' => Schema::hasColumn('orders', 'parent_order_id') ? (bool) $order->parent_order_id : false,
            'is_parent' => self::hasSubOrders($order),
            'service_type' => $order->service_type,
        ];
    }

    protected static function resolveVisuals(Order $order): array
    {
        $serviceType = ServiceType::tryFrom((string) $order->service_type);

        return match ($serviceType) {
            ServiceType::SOCIAL_CARE_VISIT => [
                'Социальный визит',
                $order->careDetails?->careService?->name ?? 'Услуга соцпомощи',
                'heroicon-o-heart',
                'rose',
            ],
            ServiceType::ECO_DISPOSAL => [
                'Эко-вывоз',
                'Утилизация вещей',
                'heroicon-o-trash',
                'emerald',
            ],
            ServiceType::ROAD_ASSIST,
            ServiceType::VEHICLE_TOW => [
                'Помощь на дороге',
                $order->metadata['incident'] ?? 'Экстренный выезд',
                'heroicon-o-truck',
                'indigo',
            ],
            ServiceType::HANDYMAN,
            ServiceType::HANDYMAN_HOURLY,
            ServiceType::HANDYMAN_FIXED => [
                'Мастер на час',
                $order->metadata['handyman_task'] ?? 'Домашние работы',
                'heroicon-o-briefcase',
                'amber',
            ],
            ServiceType::COMPLEX_REPAIR => [
                'Комплексный ремонт',
                $order->repairProject?->title ?? 'Проект ремонта',
                'heroicon-o-office-building',
                'purple',
            ],
            default => [
                $serviceType?->label() ?? 'Заказ',
                $order->address?->formatted_address ?? $order->address?->street_address,
                'heroicon-o-briefcase',
                'slate',
            ],
        };
    }

    protected static function resolveStatus(?string $status): array
    {
        return match ($status) {
            'draft' => ['Черновик', 'secondary'],
            'created' => ['Создан', 'secondary'],
            'payment_pending' => ['Ожидает оплаты', 'amber'],
            'payment_reserved' => ['Оплата зарезервирована', 'warning'],
            'confirmed' => ['Подтверждён', 'yellow'],
            'waiting_dispatch' => ['Ожидает диспетчера', 'yellow'],
            'assigned' => ['Назначен исполнитель', 'info'],
            'worker_accepted' => ['Принят исполнителем', 'info'],
            'worker_en_route' => ['Исполнитель в пути', 'info'],
            'at_pickup' => ['На точке забора', 'info'],
            'picked_up' => ['Забрано', 'info'],
            'in_progress' => ['В работе', 'blue'],
            'arrived' => ['Прибыл', 'blue'],
            'completed' => ['Выполнен', 'green'],
            'client_confirmed' => ['Подтверждено клиентом', 'success'],
            'paid_out' => ['Выплата отправлена', 'success'],
            'cancelled' => ['Отменён', 'red'],
            'refunded' => ['Возврат', 'danger'],
            'disputed' => ['Спор', 'danger'],
            'failed' => ['Ошибка', 'danger'],
            'pending', 'pending_payment' => ['Ожидает', 'amber'],
            default => [ucfirst((string) $status), 'slate'],
        };
    }

    protected static function hasSubOrders(Order $order): bool
    {
        if (!Schema::hasColumn('orders', 'parent_order_id')) {
            return false;
        }

        if ($order->relationLoaded('subOrders')) {
            return $order->subOrders->isNotEmpty();
        }

        return $order->subOrders()->exists();
    }

    protected static function resolvePaymentStatusLabel(string $status): string
    {
        return match ($status) {
            'pending', 'unpaid', 'payment_pending' => 'Ожидает оплаты',
            'reserved', 'authorized', 'payment_reserved' => 'Зарезервировано',
            'captured', 'paid', 'succeeded' => 'Оплачено',
            'refunded' => 'Возврат',
            'failed', 'declined' => 'Ошибка оплаты',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    protected static function resolvePaymentStatusColor(string $status): string
    {
        return match ($status) {
            'pending', 'unpaid', 'payment_pending' => 'amber',
            'reserved', 'authorized', 'payment_reserved' => 'blue',
            'captured', 'paid', 'succeeded' => 'green',
            'refunded' => 'violet',
            'failed', 'declined' => 'red',
            default => 'slate',
        };
    }

    protected static function resolveScenarioKey(Order $order): ?string
    {
        $metadata = is_array($order->metadata) ? $order->metadata : [];

        return isset($metadata['scenario_key']) && is_string($metadata['scenario_key'])
            ? $metadata['scenario_key']
            : null;
    }

    protected static function resolveServiceLabel(Order $order): string
    {
        $serviceType = ServiceType::tryFrom((string) $order->service_type);

        return $serviceType?->label() ?? ((string) $order->service_type ?: 'Услуга');
    }

    protected static function resolvePrice(Order $order): ?float
    {
        foreach (['final_price', 'actual_total', 'total_amount', 'estimated_total'] as $field) {
            $value = $order->{$field} ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }
}
