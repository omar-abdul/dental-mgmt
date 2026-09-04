<?php

namespace App\Http\Requests;

use App\Enums\TreatmentPlanItemAcceptance;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTreatmentPlanItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $plan = $this->route('treatmentPlan');
        $item = $this->route('treatmentPlanItem');

        return $plan instanceof TreatmentPlan
            && $item instanceof TreatmentPlanItem
            && $item->treatment_plan_id === $plan->id
            && ($this->user()?->can('updateItem', $plan) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'acceptance_status' => ['required', Rule::enum(TreatmentPlanItemAcceptance::class)],
        ];
    }
}
