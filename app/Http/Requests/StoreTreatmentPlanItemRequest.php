<?php

namespace App\Http\Requests;

use App\Concerns\AppointmentValidationRules;
use App\Enums\TreatmentPlanItemAcceptance;
use App\Models\TreatmentPlan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTreatmentPlanItemRequest extends FormRequest
{
    use AppointmentValidationRules;

    public function authorize(): bool
    {
        $plan = $this->route('treatmentPlan');

        return $plan instanceof TreatmentPlan
            && ($this->user()?->can('addItem', $plan) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fee_item_id' => ['nullable', 'integer', $this->activeFeeItemExistsRule()],
            'description' => ['required', 'string', 'max:255'],
            'tooth_fdi' => ['nullable', 'string', 'max:2'],
            'fee_cents' => ['required', 'integer', 'min:0'],
            'acceptance_status' => ['nullable', Rule::enum(TreatmentPlanItemAcceptance::class)],
        ];
    }
}
