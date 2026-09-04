<?php

namespace App\Enums;

enum InsuranceClaimStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Denied = 'denied';
    case Paid = 'paid';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
