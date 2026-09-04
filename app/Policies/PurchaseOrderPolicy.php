<?php

namespace App\Policies;

use App\Enums\ClinicRole;
use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
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

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole(...self::VIEW_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(...self::WRITE_ROLES);
    }

    public function receive(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if (! $purchaseOrder->status->isReceivable()) {
            return false;
        }

        return $user->hasRole(...self::WRITE_ROLES);
    }
}
