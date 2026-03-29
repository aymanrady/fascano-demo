<?php

namespace App\Enums\Menu;

enum Section: string
{
    case Appetizers = 'appetizers';
    case Entrees = 'entrees';
    case Desserts = 'desserts';
    case Beverages = 'beverages';

    public function label(): string
    {
        return match ($this) {
            self::Appetizers => 'Appetizers',
            self::Entrees => 'Entrees',
            self::Desserts => 'Desserts',
            self::Beverages => 'Beverages',
        };
    }
}
