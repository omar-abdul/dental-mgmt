<?php

namespace App\Enums;

enum ImagingOrderType: string
{
    case Panoramic = 'panoramic';
    case Bitewing = 'bitewing';
    case Periapical = 'periapical';
    case Cbct = 'cbct';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Panoramic => 'Panoramic',
            self::Bitewing => 'Bitewing',
            self::Periapical => 'Periapical',
            self::Cbct => 'CBCT',
            self::Other => 'Other',
        };
    }
}
