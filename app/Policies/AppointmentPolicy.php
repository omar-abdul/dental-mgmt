<?php

namespace App\Policies;

use App\Enums\AppointmentStatus;
use App\Enums\ClinicRole;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
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
    private const BOOK_ROLES = [
        ClinicRole::Admin,
        ClinicRole::Receptionist,
    ];

    /**
     * @var list<ClinicRole>
     */
    private const CHECK_IN_ROLES = [
        ClinicRole::Admin,
        ClinicRole::Receptionist,
        ClinicRole::Nurse,
    ];

    public function viewAny(User $user): bool
    {
        return $user->hasRole(...self::VIEW_ROLES);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->hasRole(...self::VIEW_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(...self::BOOK_ROLES);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        if ($this->isTerminal($appointment)) {
            return false;
        }

        return $user->hasRole(...self::BOOK_ROLES);
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        if ($this->isTerminal($appointment)) {
            return false;
        }

        return $user->hasRole(...self::BOOK_ROLES);
    }

    public function checkIn(User $user, Appointment $appointment): bool
    {
        if ($this->isTerminal($appointment)) {
            return false;
        }

        return $user->hasRole(...self::CHECK_IN_ROLES);
    }

    private function isTerminal(Appointment $appointment): bool
    {
        return in_array($appointment->status, [
            AppointmentStatus::Cancelled,
            AppointmentStatus::Completed,
            AppointmentStatus::NoShow,
        ], true);
    }
}
