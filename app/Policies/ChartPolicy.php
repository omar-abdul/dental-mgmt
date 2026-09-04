<?php

namespace App\Policies;

use App\Enums\ClinicRole;
use App\Models\Patient;
use App\Models\User;

class ChartPolicy
{
    /**
     * @var list<ClinicRole>
     */
    private const VIEW_ROLES = [
        ClinicRole::Admin,
        ClinicRole::Dentist,
        ClinicRole::Nurse,
    ];

    /**
     * @var list<ClinicRole>
     */
    private const WRITE_ROLES = [
        ClinicRole::Admin,
        ClinicRole::Dentist,
    ];

    public function viewAny(User $user): bool
    {
        return $user->hasRole(...self::VIEW_ROLES);
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->hasRole(...self::VIEW_ROLES);
    }

    public function updateOdontogram(User $user, Patient $patient): bool
    {
        return $user->hasRole(...self::WRITE_ROLES);
    }
}
