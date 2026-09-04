<?php

namespace App\Enums;

enum ToothStatus: string
{
    case Healthy = 'healthy';
    case Caries = 'caries';
    case Filled = 'filled';
    case Missing = 'missing';
    case Extracted = 'extracted';
    case RootCanal = 'root_canal';
    case Crown = 'crown';
    case Implant = 'implant';
    case Bridge = 'bridge';
    case Fractured = 'fractured';
    case Impacted = 'impacted';
    case Other = 'other';
}
