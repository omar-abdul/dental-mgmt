<?php

namespace App\Policies;

use App\Enums\ClinicRole;
use App\Models\PaymentPlan;
use App\Models\User;

class PaymentPlanPolicy
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

    public function view(User $user, PaymentPlan $paymentPlan): bool
    {
        return $user->hasRole(...self::MANAGE_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(...self::MANAGE_ROLES);
    }
}
