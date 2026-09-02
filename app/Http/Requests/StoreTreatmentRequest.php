<?php

namespace App\Http\Requests;

use App\Concerns\AppointmentValidationRules;
use App\Enums\TreatmentStatus;
use App\Models\Appointment;
use App\Models\Treatment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTreatmentRequest extends FormRequest
{
    use AppointmentValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Treatment::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', $this->bookablePatientExistsRule()],
            'dentist_id' => ['required', 'integer', $this->activeDentistExistsRule()],
            'appointment_id' => [
                'nullable',
                'integer',
                $this->linkableAppointmentExistsRule(),
                Rule::unique('treatments', 'appointment_id'),
            ],
            'diagnosis' => ['required', 'string', 'max:65535'],
            'diagnosed_at' => ['nullable', 'date'],
            'status' => ['nullable', Rule::enum(TreatmentStatus::class)],
            'notes' => ['nullable', 'string', 'max:65535'],
            'procedures' => ['required', 'array', 'min:1'],
            'procedures.*.fee_item_id' => ['required', 'integer', $this->activeFeeItemExistsRule()],
            'procedures.*.quantity' => ['required', 'integer', 'min:1'],
            'procedures.*.tooth_fdi' => ['nullable', 'string', 'max:10'],
            'prescription_items' => ['required', 'array', 'min:1'],
            'prescription_items.*.medication' => ['required', 'string', 'max:255'],
            'prescription_items.*.dosage' => ['required', 'string', 'max:255'],
            'prescription_items.*.instructions' => ['required', 'string', 'max:65535'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $appointmentId = $this->integer('appointment_id') ?: null;

            if ($appointmentId === null) {
                return;
            }

            $appointment = Appointment::query()->find($appointmentId);

            if ($appointment === null) {
                return;
            }

            if ($appointment->patient_id !== $this->integer('patient_id')) {
                $validator->errors()->add('appointment_id', __('The appointment does not belong to this patient.'));
            }
        });
    }
}
