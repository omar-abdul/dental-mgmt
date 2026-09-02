<?php

namespace App\Policies;

use App\Enums\ClinicRole;
use App\Models\InventoryItem;
use App\Models\User;

class InventoryItemPolicy
{
    /**
     * @var list<ClinicRole>
     */
    private const VIEW_ROLES = [
        ClinicRole::Admin,
        ClinicRole::Dentist,
        ClinicRole::Receptionist,
        ClinicRole::Nurse,
    ];

    /**
     * @var list<ClinicRole>
     */
    private const WRITE_ROLES = [
        ClinicRole::Admin,
        ClinicRole::Receptionist,
        ClinicRole::Nurse,
    ];

    public function viewAny(User $user): bool
    {
        return $user->hasRole(...self::VIEW_ROLES);
    }

    public function view(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->hasRole(...self::VIEW_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(...self::WRITE_ROLES);
    }

    public function adjust(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->hasRole(...self::WRITE_ROLES);
    }
}
