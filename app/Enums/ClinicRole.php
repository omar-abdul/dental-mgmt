<?php

namespace App\Enums;

enum ClinicRole: string
{
    case Admin = 'admin';
    case Dentist = 'dentist';
    case Receptionist = 'receptionist';
    case Nurse = 'nurse';
    case Accountant = 'accountant';
    case Lab = 'lab';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Dentist => 'Dentist',
            self::Receptionist => 'Receptionist',
            self::Nurse => 'Nurse',
            self::Accountant => 'Accountant',
            self::Lab => 'Lab',
        };
    }

    /**
     * @return list<string>
     */
    public function viewableModules(): array
    {
        $modules = [
            'dashboard',
            'patients',
            'appointments',
            'treatments',
            'billing',
            'inventory',
            'reports',
            'settings',
        ];

        return array_values(array_filter(
            $modules,
            fn (string $module): bool => $this->canViewModule($module),
        ));
    }

    public function canViewModule(string $module): bool
    {
        return match ($module) {
            'dashboard' => true,
            'patients' => in_array($this, [self::Admin, self::Dentist, self::Receptionist, self::Nurse], true),
            'appointments' => in_array($this, [self::Admin, self::Dentist, self::Receptionist, self::Nurse], true),
            'treatments' => in_array($this, [self::Admin, self::Dentist, self::Nurse, self::Receptionist], true),
            'billing' => in_array($this, [self::Admin, self::Dentist, self::Receptionist, self::Accountant], true),
            'inventory' => in_array($this, [self::Admin, self::Dentist, self::Receptionist, self::Nurse], true),
            'reports' => true,
            'settings' => true,
            default => false,
        };
    }
}
