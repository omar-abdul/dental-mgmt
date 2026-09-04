<?php

namespace App\Policies;

use App\Enums\ClinicRole;
use App\Models\Encounter;
use App\Models\User;

class EncounterPolicy
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

    public function view(User $user, Encounter $encounter): bool
    {
        return $user->hasRole(...self::VIEW_ROLES);
    }

    public function updateSoap(User $user, Encounter $encounter): bool
    {
        if (! $user->hasRole(...self::WRITE_ROLES)) {
            return false;
        }

        $encounter->loadMissing('soapNote');

        return $encounter->soapNote !== null && ! $encounter->soapNote->isSigned();
    }

    public function sign(User $user, Encounter $encounter): bool
    {
        if (! $user->hasRole(...self::WRITE_ROLES)) {
            return false;
        }

        $encounter->loadMissing('soapNote');

        return $encounter->soapNote !== null && ! $encounter->soapNote->isSigned();
    }

    public function amend(User $user, Encounter $encounter): bool
    {
        if (! $user->hasRole(...self::WRITE_ROLES)) {
            return false;
        }

        $encounter->loadMissing('soapNote');

        return $encounter->soapNote !== null && $encounter->soapNote->isSigned();
    }
}
