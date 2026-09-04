<?php

namespace App\Policies;

use App\Enums\ClinicRole;
use App\Models\ImagingOrder;
use App\Models\User;

class ImagingOrderPolicy
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

    public function view(User $user, ImagingOrder $imagingOrder): bool
    {
        return $user->hasRole(...self::VIEW_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(...self::WRITE_ROLES);
    }
}
