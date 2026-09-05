<?php

namespace App\Concerns;

use App\Enums\AppointmentStatus;
use App\Enums\PatientStatus;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

trait AppointmentValidationRules
{
    protected function bookablePatientExistsRule(): Exists
    {
        return Rule::exists('patients', 'id')->where(function ($query): void {
            $query->where('status', PatientStatus::Active->value)
                ->whereNull('deleted_at');
        });
    }

    protected function activeDentistExistsRule(): Exists
    {
        return Rule::exists('dentists', 'id')->where('is_active', true);
    }

    protected function activeChairExistsRule(): Exists
    {
        return Rule::exists('chairs', 'id')->where('is_active', true);
    }

    protected function activeFeeItemExistsRule(): Exists
    {
        return Rule::exists('fee_items', 'id')->where('is_active', true);
    }

    /**
     * @return list<string>
     */
    protected function linkableAppointmentStatuses(): array
    {
        return [
            AppointmentStatus::Scheduled->value,
            AppointmentStatus::Confirmed->value,
            AppointmentStatus::CheckedIn->value,
            AppointmentStatus::InProgress->value,
            AppointmentStatus::InTreatment->value,
            AppointmentStatus::Rescheduled->value,
        ];
    }

    protected function linkableAppointmentExistsRule(): Exists
    {
        return Rule::exists('appointments', 'id')->whereIn('status', $this->linkableAppointmentStatuses());
    }
}
