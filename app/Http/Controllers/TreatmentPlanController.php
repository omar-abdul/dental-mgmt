<?php

namespace App\Http\Controllers;

use App\Enums\TreatmentPlanItemAcceptance;
use App\Http\Requests\StoreTreatmentPlanItemRequest;
use App\Http\Requests\UpdateTreatmentPlanItemRequest;
use App\Models\FeeItem;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TreatmentPlanController extends Controller
{
    public function storeItem(StoreTreatmentPlanItemRequest $request, TreatmentPlan $treatmentPlan): RedirectResponse
    {
        Gate::authorize('addItem', $treatmentPlan);

        $validated = $request->validated();
        $userId = $request->user()?->id;

        $feeCents = (int) $validated['fee_cents'];

        if (isset($validated['fee_item_id'])) {
            $feeItem = FeeItem::query()->find($validated['fee_item_id']);

            if ($feeItem !== null && $feeCents === 0) {
                $feeCents = $feeItem->price_cents;
            }
        }

        TreatmentPlanItem::query()->create([
            'treatment_plan_id' => $treatmentPlan->id,
            'fee_item_id' => $validated['fee_item_id'] ?? null,
            'description' => $validated['description'],
            'tooth_fdi' => $validated['tooth_fdi'] ?? null,
            'fee_cents' => $feeCents,
            'acceptance_status' => isset($validated['acceptance_status'])
                ? TreatmentPlanItemAcceptance::from($validated['acceptance_status'])
                : TreatmentPlanItemAcceptance::Proposed,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Plan item added.')]);

        return to_route('patients.chart', $treatmentPlan->patient_id);
    }

    public function updateItem(
        UpdateTreatmentPlanItemRequest $request,
        TreatmentPlan $treatmentPlan,
        TreatmentPlanItem $treatmentPlanItem,
    ): RedirectResponse {
        Gate::authorize('updateItem', $treatmentPlan);

        $validated = $request->validated();
        $userId = $request->user()?->id;

        $treatmentPlanItem->update([
            'acceptance_status' => TreatmentPlanItemAcceptance::from($validated['acceptance_status']),
            'updated_by' => $userId,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Plan item updated.')]);

        return to_route('patients.chart', $treatmentPlan->patient_id);
    }
}
