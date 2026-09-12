<?php

namespace App\Policies;

use App\Enums\ClinicRole;
use App\Enums\PatientStatus;
use App\Models\Patient;
use App\Models\User;

class PatientPolicy
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
    ];

    /**
     * Picker typeahead. Lab may search identity to create orders without
     * the Patients module (viewAny).
     *
     * @var list<ClinicRole>
     */
    private const SEARCH_ROLES = [
        ...self::VIEW_ROLES,
        ClinicRole::Lab,
    ];

    public function viewAny(User $user): bool
    {
        return $user->hasRole(...self::VIEW_ROLES);
    }

    public function search(User $user): bool
    {
        return $user->hasRole(...self::SEARCH_ROLES);
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->hasRole(...self::VIEW_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(...self::WRITE_ROLES);
    }

    public function update(User $user, Patient $patient): bool
    {
        if ($this->isArchived($patient)) {
            return false;
        }

        return $user->hasRole(...self::WRITE_ROLES);
    }

    public function delete(User $user, Patient $patient): bool
    {
        if ($this->isArchived($patient)) {
            return false;
        }

        return $user->hasRole(...self::WRITE_ROLES);
    }

    private function isArchived(Patient $patient): bool
    {
        return $patient->trashed() || $patient->status === PatientStatus::Archived;
    }
}
