<?php

namespace App\Http\Requests;

use App\Models\Dentist;
use App\Models\Encounter;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLabOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', LabOrder::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', Rule::exists(Patient::class, 'id')],
            'dentist_id' => ['required', Rule::exists(Dentist::class, 'id')],
            'treatment_id' => ['nullable', Rule::exists(Treatment::class, 'id')],
            'encounter_id' => ['nullable', Rule::exists(Encounter::class, 'id')],
            'description' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
