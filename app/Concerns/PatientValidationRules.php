<?php

namespace App\Concerns;

use App\Enums\Gender;
use App\Models\Patient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait PatientValidationRules
{
    protected function prepareForValidation(): void
    {
        /** @var FormRequest $this */
        $merge = [];

        foreach (['allergies', 'conditions', 'medications'] as $field) {
            if ($this->has($field)) {
                $merge[$field] = $this->filterEmptyMedicalRows($this->input($field));
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function filterEmptyMedicalRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->filter(fn (mixed $row): bool => is_array($row) && filled($row['label'] ?? null))
            ->values()
            ->all();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function patientIdentityRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'confirm_duplicate' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function patientMedicalRules(): array
    {
        return [
            'allergies' => ['nullable', 'array'],
            'allergies.*.id' => ['sometimes', 'nullable', 'integer'],
            'allergies.*.label' => ['required', 'string', 'max:255'],
            'allergies.*.is_critical' => ['sometimes', 'boolean'],
            'conditions' => ['nullable', 'array'],
            'conditions.*.id' => ['sometimes', 'nullable', 'integer'],
            'conditions.*.label' => ['required', 'string', 'max:255'],
            'conditions.*.is_critical' => ['sometimes', 'boolean'],
            'medications' => ['nullable', 'array'],
            'medications.*.id' => ['sometimes', 'nullable', 'integer'],
            'medications.*.label' => ['required', 'string', 'max:255'],
            'medications.*.is_critical' => ['sometimes', 'boolean'],
            'emergency_contact.name' => ['nullable', 'string', 'max:255'],
            'emergency_contact.relationship' => ['nullable', 'string', 'max:255'],
            'emergency_contact.phone' => ['nullable', 'string', 'max:50'],
        ];
    }

    protected function validateDuplicatePatient(Validator $validator, ?int $excludePatientId = null): void
    {
        /** @var FormRequest $this */
        if ($validator->errors()->isNotEmpty() || $this->boolean('confirm_duplicate')) {
            return;
        }

        $query = Patient::query()
            ->withTrashed()
            ->whereRaw('LOWER(TRIM(first_name)) = ?', [strtolower(trim($this->string('first_name')->value()))])
            ->whereRaw('LOWER(TRIM(last_name)) = ?', [strtolower(trim($this->string('last_name')->value()))])
            ->whereDate('date_of_birth', $this->date('date_of_birth'));

        if ($excludePatientId !== null) {
            $query->where('id', '!=', $excludePatientId);
        }

        $duplicate = $query->first();

        if ($duplicate !== null) {
            $validator->errors()->add(
                'duplicate',
                __('A patient with this name and date of birth already exists (:number).', [
                    'number' => $duplicate->patient_number,
                ]),
            );
        }
    }
}
