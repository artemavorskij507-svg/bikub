<?php

namespace App\Enums;

enum SubstitutionPolicy: string
{
    case STRICT = 'strict';   // без замен
    case AI = 'ai';           // авто подбор альтернатив
    case CONTACT = 'contact'; // спрашивать клиента

    public function label(): string
    {
        return match ($this) {
            self::STRICT => 'Без замен',
            self::AI => 'AI-предложения',
            self::CONTACT => 'Связаться с клиентом',
        };
    }
}
