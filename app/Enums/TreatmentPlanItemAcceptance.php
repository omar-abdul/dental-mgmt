<?php

namespace App\Enums;

enum TreatmentPlanItemAcceptance: string
{
    case Proposed = 'proposed';
    case Accepted = 'accepted';
    case Declined = 'declined';
}
