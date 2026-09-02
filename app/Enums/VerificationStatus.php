<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case VerificationRequired = 'verification_required';
    case Verified = 'verified';
    case Failed = 'failed';
}
