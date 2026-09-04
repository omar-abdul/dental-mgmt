<?php

namespace App\Policies;

use App\Enums\ClinicRole;
use App\Models\TreatmentPlan;
use App\Models\User;

class TreatmentPlanPolicy
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

    public function view(User $user, TreatmentPlan $treatmentPlan): bool
    {
        return $user->hasRole(...self::VIEW_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(...self::WRITE_ROLES);
    }

    public function update(User $user, TreatmentPlan $treatmentPlan): bool
    {
        return $user->hasRole(...self::WRITE_ROLES);
    }

    public function addItem(User $user, TreatmentPlan $treatmentPlan): bool
    {
        return $user->hasRole(...self::WRITE_ROLES);
    }

    public function updateItem(User $user, TreatmentPlan $treatmentPlan): bool
    {
        return $user->hasRole(...self::WRITE_ROLES);
    }
}
