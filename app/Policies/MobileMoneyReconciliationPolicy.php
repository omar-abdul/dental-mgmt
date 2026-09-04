<?php

namespace App\Policies;

use App\Enums\ClinicRole;
use App\Models\MobileMoneyReconciliation;
use App\Models\User;

class MobileMoneyReconciliationPolicy
{
    /**
     * @var list<ClinicRole>
     */
    private const MANAGE_ROLES = [
        ClinicRole::Admin,
        ClinicRole::Accountant,
    ];

    public function viewAny(User $user): bool
    {
        return $user->hasRole(...self::MANAGE_ROLES);
    }

    public function view(User $user, MobileMoneyReconciliation $mobileMoneyReconciliation): bool
    {
        return $user->hasRole(...self::MANAGE_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(...self::MANAGE_ROLES);
    }
}
