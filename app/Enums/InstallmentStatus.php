<?php

namespace App\Enums;

enum InstallmentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Overdue = 'overdue';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
