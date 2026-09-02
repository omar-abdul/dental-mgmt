<?php

namespace App\Http\Requests;

use App\Concerns\AppointmentValidationRules;
use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    use AppointmentValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Appointment::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', $this->bookablePatientExistsRule()],
            'dentist_id' => ['required', 'integer', $this->activeDentistExistsRule()],
            'chair_id' => ['required', 'integer', $this->activeChairExistsRule()],
            'fee_item_id' => ['nullable', 'integer', 'exists:fee_items,id'],
            'starts_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
