<?php

namespace App\Enums;

enum PaymentPlanStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
