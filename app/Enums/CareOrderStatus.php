<?php

namespace App\Enums;

enum CareOrderStatus: string
{
    case PENDING = 'PENDING';
    case SCHEDULED = 'SCHEDULED';
    case ACCEPTED_BY_HELPER = 'ACCEPTED_BY_HELPER';
    case EN_ROUTE = 'EN_ROUTE';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';
    case CANCELLED_BY_CLIENT = 'CANCELLED_BY_CLIENT';
    case CANCELLED_BY_OPERATOR = 'CANCELLED_BY_OPERATOR';
    case CANCELLED_BY_TRUSTED_CONTACT = 'CANCELLED_BY_TRUSTED_CONTACT';
    case NO_SHOW_CLIENT = 'NO_SHOW_CLIENT';
    case NO_SHOW_HELPER = 'NO_SHOW_HELPER';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает',
            self::SCHEDULED => 'Запланирован',
            self::ACCEPTED_BY_HELPER => 'Принят помощником',
            self::EN_ROUTE => 'В пути',
            self::IN_PROGRESS => 'В процессе',
            self::COMPLETED => 'Завершён',
            self::CANCELLED => 'Отменён',
            self::CANCELLED_BY_CLIENT => 'Отменён клиентом',
            self::CANCELLED_BY_OPERATOR => 'Отменён оператором',
            self::CANCELLED_BY_TRUSTED_CONTACT => 'Отменён доверенным лицом',
            self::NO_SHOW_CLIENT => 'Клиент не явился',
            self::NO_SHOW_HELPER => 'Помощник не явился',
        };
    }

    public static function finalStatuses(): array
    {
        return [
            self::COMPLETED->value,
            self::CANCELLED->value,
            self::CANCELLED_BY_CLIENT->value,
            self::CANCELLED_BY_OPERATOR->value,
            self::CANCELLED_BY_TRUSTED_CONTACT->value,
            self::NO_SHOW_CLIENT->value,
            self::NO_SHOW_HELPER->value,
        ];
    }

    public function isFinal(): bool
    {
        return in_array($this->value, self::finalStatuses());
    }
}
