<?php

namespace App\Enum;

enum PropertyType: string
{
    case SALE = 'sale';
    case RENT = 'rent';

    public function label(): string
    {
        return match($this) {
            self::SALE => 'Vente',
            self::RENT => 'Location',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::SALE => 'home',
            self::RENT => 'key',
        };
    }
}
