<?php

namespace App\Policies;

use App\Enums\ClinicRole;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * @var list<ClinicRole>
     */
    private const VIEW_ROLES = [
        ClinicRole::Admin,
        ClinicRole::Dentist,
        ClinicRole::Receptionist,
        ClinicRole::Accountant,
    ];

    /**
     * @var list<ClinicRole>
     */
    private const GENERATE_PAY_ROLES = [
        ClinicRole::Admin,
        ClinicRole::Receptionist,
        ClinicRole::Accountant,
    ];

    /**
     * @var list<ClinicRole>
     */
    private const REFUND_ROLES = [
        ClinicRole::Admin,
        ClinicRole::Accountant,
    ];

    public function viewAny(User $user): bool
    {
        return $user->hasRole(...self::VIEW_ROLES);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasRole(...self::VIEW_ROLES);
    }

    public function generate(User $user): bool
    {
        return $user->hasRole(...self::GENERATE_PAY_ROLES);
    }

    public function pay(User $user, Invoice $invoice): bool
    {
        return $user->hasRole(...self::GENERATE_PAY_ROLES);
    }

    public function refund(User $user, Invoice $invoice): bool
    {
        return $user->hasRole(...self::REFUND_ROLES);
    }
}
