<?php

namespace App\Http\Requests;

use App\Concerns\AppointmentValidationRules;
use App\Models\TreatmentPlan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTreatmentPlanRequest extends FormRequest
{
    use AppointmentValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', TreatmentPlan::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'dentist_id' => ['required', 'integer', $this->activeDentistExistsRule()],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:65535'],
        ];
    }
}
