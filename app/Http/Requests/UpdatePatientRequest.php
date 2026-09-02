<?php

namespace App\Http\Requests;

use App\Concerns\PatientValidationRules;
use App\Models\Patient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePatientRequest extends FormRequest
{
    use PatientValidationRules;

    public function authorize(): bool
    {
        $patient = $this->route('patient');

        return $patient !== null && ($this->user()?->can('update', $patient) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(
            $this->patientIdentityRules(),
            $this->patientMedicalRules(),
        );
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Patient|null $patient */
            $patient = $this->route('patient');

            $this->validateDuplicatePatient($validator, $patient?->id);
        });
    }
}
