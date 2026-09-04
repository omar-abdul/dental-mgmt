<?php

namespace App\Policies;

use App\Enums\ClinicRole;
use App\Models\LabOrder;
use App\Models\User;

class LabOrderPolicy
{
    /**
     * @var list<ClinicRole>
     */
    private const ACCESS_ROLES = [
        ClinicRole::Admin,
        ClinicRole::Dentist,
        ClinicRole::Lab,
    ];

    public function viewAny(User $user): bool
    {
        return $user->hasRole(...self::ACCESS_ROLES);
    }

    public function view(User $user, LabOrder $labOrder): bool
    {
        return $user->hasRole(...self::ACCESS_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(...self::ACCESS_ROLES);
    }

    public function update(User $user, LabOrder $labOrder): bool
    {
        if ($labOrder->status->isTerminal()) {
            return false;
        }

        return $user->hasRole(...self::ACCESS_ROLES);
    }

    public function transition(User $user, LabOrder $labOrder): bool
    {
        if ($labOrder->status->isTerminal()) {
            return false;
        }

        return $user->hasRole(...self::ACCESS_ROLES);
    }
}
