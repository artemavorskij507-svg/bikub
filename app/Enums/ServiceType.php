<?php

namespace App\Enums;

enum ServiceType: string
{
    case ROAD_ASSIST = 'road_assist';
    case VEHICLE_TOW = 'vehicle_tow';
    case INSPECTION_BASIC = 'inspection_basic';
    case INSPECTION_FULL = 'inspection_full';
    case INSPECTION_SERVICE = 'inspection_service';
    case GROCERY_DELIVERY = 'grocery_delivery';
    case HANDYMAN = 'handyman';
    case HANDYMAN_HOURLY = 'handyman_hourly';
    case HANDYMAN_FIXED = 'handyman_fixed';
    case COMPLEX_REPAIR = 'complex_repair';
    case ECO_DISPOSAL = 'eco_disposal';
    case SOCIAL_CARE_VISIT = 'social_care_visit';
    case ERRAND = 'errand';

    public function label(): string
    {
        return match ($this) {
            self::ROAD_ASSIST => 'Помощь на дороге',
            self::VEHICLE_TOW => 'Эвакуация',
            self::INSPECTION_BASIC => 'Базовый осмотр',
            self::INSPECTION_FULL => 'Полный осмотр',
            self::INSPECTION_SERVICE => 'Сопровождение сделки',
            self::GROCERY_DELIVERY => 'Доставка продуктов',
            self::HANDYMAN => 'Мастер на дом',
            self::HANDYMAN_HOURLY => 'Мастер (почасово)',
            self::HANDYMAN_FIXED => 'Мастер (фиксированная цена)',
            self::COMPLEX_REPAIR => 'Комплексный ремонт',
            self::ECO_DISPOSAL => 'Эко-услуги и утилизация',
            self::SOCIAL_CARE_VISIT => 'Социальный визит / забота',
            self::ERRAND => 'Индивидуальное поручение',
        };
    }
}
