<?php

namespace App\Policies;

use App\Enums\ClinicRole;
use App\Models\DailyCashClosing;
use App\Models\User;

class DailyCashClosingPolicy
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

    public function view(User $user, DailyCashClosing $dailyCashClosing): bool
    {
        return $user->hasRole(...self::MANAGE_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(...self::MANAGE_ROLES);
    }
}
