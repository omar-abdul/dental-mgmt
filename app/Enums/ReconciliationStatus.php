<?php

namespace App\Enums;

enum ReconciliationStatus: string
{
    case Reconciled = 'reconciled';
    case Discrepancy = 'discrepancy';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
