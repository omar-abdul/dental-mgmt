<?php

namespace App\Enums;

enum MobileMoneyProvider: string
{
    case Telesom = 'Telesom';
    case Golis = 'Golis';
    case Somtel = 'Somtel';
    case Somlink = 'Somlink';
}
