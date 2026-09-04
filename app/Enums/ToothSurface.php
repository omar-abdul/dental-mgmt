<?php

namespace App\Enums;

enum ToothSurface: string
{
    case Mesial = 'M';
    case Distal = 'D';
    case Occlusal = 'O';
    case Incisal = 'I';
    case Buccal = 'B';
    case Lingual = 'L';
}
