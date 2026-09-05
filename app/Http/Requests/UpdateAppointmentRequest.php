<?php

namespace App\Http\Requests;

use App\Concerns\AppointmentValidationRules;
use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    use AppointmentValidationRules;

    public function authorize(): bool
    {
        /** @var Appointment $appointment */
        $appointment = $this->route('appointment');

        return $this->user()?->can('update', $appointment) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['sometimes', 'required', 'integer', $this->bookablePatientExistsRule()],
            'dentist_id' => ['sometimes', 'required', 'integer', $this->activeDentistExistsRule()],
            'chair_id' => ['sometimes', 'required', 'integer', $this->activeChairExistsRule()],
            'fee_item_id' => ['nullable', 'integer', $this->activeFeeItemExistsRule()],
            'starts_at' => ['sometimes', 'required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
