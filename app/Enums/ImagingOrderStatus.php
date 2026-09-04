<?php

namespace App\Enums;

enum ImagingOrderStatus: string
{
    case Ordered = 'ordered';
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Ordered => 'Ordered',
            self::Scheduled => 'Scheduled',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }
}
