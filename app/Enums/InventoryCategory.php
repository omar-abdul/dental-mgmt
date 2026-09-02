<?php

namespace App\Enums;

enum InventoryCategory: string
{
    case DentalMaterials = 'dental_materials';
    case Medicines = 'medicines';
    case Instruments = 'instruments';
    case Ppe = 'ppe';
    case Consumables = 'consumables';
    case OfficeSupplies = 'office_supplies';

    public function label(): string
    {
        return match ($this) {
            self::DentalMaterials => 'Dental Materials',
            self::Medicines => 'Medicines',
            self::Instruments => 'Instruments',
            self::Ppe => 'PPE',
            self::Consumables => 'Consumables',
            self::OfficeSupplies => 'Office Supplies',
        };
    }
}
