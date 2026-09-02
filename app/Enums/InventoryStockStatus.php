<?php

namespace App\Enums;

enum InventoryStockStatus: string
{
    case Out = 'out';
    case Low = 'low';
    case InStock = 'in_stock';

    public function label(): string
    {
        return match ($this) {
            self::Out => 'Out of stock',
            self::Low => 'Low stock',
            self::InStock => 'In stock',
        };
    }
}
