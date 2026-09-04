<?php

namespace App\Policies;

use App\Enums\ClinicRole;
use App\Models\InsuranceClaim;
use App\Models\User;

class InsuranceClaimPolicy
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

    public function view(User $user, InsuranceClaim $insuranceClaim): bool
    {
        return $user->hasRole(...self::MANAGE_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(...self::MANAGE_ROLES);
    }

    public function update(User $user, InsuranceClaim $insuranceClaim): bool
    {
        return $user->hasRole(...self::MANAGE_ROLES);
    }
}
