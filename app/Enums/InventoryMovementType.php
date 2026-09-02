<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case Consumption = 'consumption';
}
