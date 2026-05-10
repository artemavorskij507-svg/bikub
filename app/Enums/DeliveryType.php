<?php

namespace App\Enums;

enum DeliveryType: string
{
    case GROCERY = 'grocery';
    case BULKY = 'bulky';
    case FOOD = 'food';

    public function label(): string
    {
        return match ($this) {
            self::GROCERY => 'Продукти',
            self::BULKY => 'Крупногабарит',
            self::FOOD => 'Готова їжа',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::GROCERY => 'heroicon-o-shopping-bag',
            self::BULKY => 'heroicon-o-cube',
            self::FOOD => 'heroicon-o-beaker',
        };
    }
}
