<?php

namespace App\Policies;

use App\Enums\ClinicRole;
use App\Enums\TreatmentStatus;
use App\Models\Treatment;
use App\Models\User;

class TreatmentPolicy
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
        ClinicRole::Dentist,
    ];

    public function viewAny(User $user): bool
    {
        return $user->hasRole(...self::VIEW_ROLES);
    }

    public function view(User $user, Treatment $treatment): bool
    {
        return $user->hasRole(...self::VIEW_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(...self::WRITE_ROLES);
    }

    public function update(User $user, Treatment $treatment): bool
    {
        if ($this->isTerminal($treatment)) {
            return false;
        }

        return $user->hasRole(...self::WRITE_ROLES);
    }

    public function complete(User $user, Treatment $treatment): bool
    {
        if ($this->isTerminal($treatment)) {
            return false;
        }

        return $user->hasRole(...self::WRITE_ROLES);
    }

    private function isTerminal(Treatment $treatment): bool
    {
        return in_array($treatment->status, [
            TreatmentStatus::Completed,
            TreatmentStatus::Cancelled,
        ], true);
    }
}
