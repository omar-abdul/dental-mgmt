<?php

namespace App\Http\Requests;

use App\Enums\ImagingOrderStatus;
use App\Enums\ImagingOrderType;
use App\Models\Dentist;
use App\Models\Encounter;
use App\Models\ImagingOrder;
use App\Models\Patient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreImagingOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ImagingOrder::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', Rule::exists(Patient::class, 'id')],
            'dentist_id' => ['required', Rule::exists(Dentist::class, 'id')],
            'encounter_id' => ['nullable', Rule::exists(Encounter::class, 'id')],
            'type' => ['required', Rule::enum(ImagingOrderType::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', Rule::enum(ImagingOrderStatus::class)],
            'result_findings' => ['nullable', 'string', 'max:5000'],
            'result_impression' => ['nullable', 'string', 'max:5000'],
            'file' => [
                'nullable',
                File::types(['jpg', 'jpeg', 'png', 'pdf'])->max(10240),
            ],
        ];
    }
}
