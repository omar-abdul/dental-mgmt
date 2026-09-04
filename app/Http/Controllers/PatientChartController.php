<?php

namespace App\Http\Controllers;

use App\Enums\ToothStatus;
use App\Enums\ToothSurface;
use App\Enums\TreatmentPlanItemAcceptance;
use App\Http\Requests\StoreTreatmentPlanRequest;
use App\Http\Requests\UpdateOdontogramRequest;
use App\Models\Dentist;
use App\Models\Encounter;
use App\Models\FeeItem;
use App\Models\OdontogramSurface;
use App\Models\OdontogramTooth;
use App\Models\Patient;
use App\Models\ToothHistory;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Policies\ChartPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PatientChartController extends Controller
{
    public function show(Request $request, Patient $patient): Response
    {
        Gate::authorize('view', $patient);
        abort_unless((new ChartPolicy)->view($request->user(), $patient), 403);

        $patient->load([
            'odontogramTeeth.surfaces',
            'toothHistories' => fn ($query) => $query->latest()->limit(20),
            'treatmentPlans.items.feeItem',
            'encounters' => fn ($query) => $query->latest('visited_at')->limit(10),
        ]);

        $user = $request->user();

        return Inertia::render('chart/PatientChart', [
            'patient' => [
                'id' => $patient->id,
                'full_name' => "{$patient->first_name} {$patient->last_name}",
                'patient_number' => $patient->patient_number,
            ],
            'teeth' => $patient->odontogramTeeth->map(fn (OdontogramTooth $tooth) => [
                'tooth_fdi' => $tooth->tooth_fdi,
                'status' => $tooth->status->value,
                'surfaces' => $tooth->surfaces->map(fn (OdontogramSurface $surface) => $surface->surface->value)->values(),
            ])->values(),
            'toothHistory' => $patient->toothHistories->map(fn (ToothHistory $entry) => [
                'id' => $entry->id,
                'tooth_fdi' => $entry->tooth_fdi,
                'previous_status' => $entry->previous_status?->value,
                'new_status' => $entry->new_status->value,
                'surfaces' => $entry->surfaces ?? [],
                'notes' => $entry->notes,
                'recorded_at_formatted' => $entry->created_at?->format('M j, Y g:i A'),
            ])->values(),
            'treatmentPlans' => $patient->treatmentPlans->map(fn (TreatmentPlan $plan) => [
                'id' => $plan->id,
                'title' => $plan->title,
                'notes' => $plan->notes,
                'items' => $plan->items->map(fn (TreatmentPlanItem $item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'tooth_fdi' => $item->tooth_fdi,
                    'fee_cents' => $item->fee_cents,
                    'fee_formatted' => '$'.number_format($item->fee_cents / 100, 2),
                    'acceptance_status' => $item->acceptance_status->value,
                    'acceptance_label' => ucfirst($item->acceptance_status->value),
                ])->values(),
            ])->values(),
            'recentEncounters' => $patient->encounters->map(fn ($encounter) => [
                'id' => $encounter->id,
                'number' => $encounter->number,
                'visited_at_formatted' => $encounter->visited_at->format('M j, Y g:i A'),
            ])->values(),
            'statusOptions' => collect(ToothStatus::cases())->map(fn (ToothStatus $status) => [
                'value' => $status->value,
                'label' => ucfirst(str_replace('_', ' ', $status->value)),
            ])->values(),
            'surfaceOptions' => collect(ToothSurface::cases())->map(fn (ToothSurface $surface) => $surface->value)->values(),
            'acceptanceOptions' => collect(TreatmentPlanItemAcceptance::cases())->map(fn (TreatmentPlanItemAcceptance $status) => [
                'value' => $status->value,
                'label' => ucfirst($status->value),
            ])->values(),
            'dentists' => Dentist::query()
                ->where('is_active', true)
                ->orderBy('display_name')
                ->get(['id', 'display_name'])
                ->map(fn (Dentist $dentist) => ['id' => $dentist->id, 'label' => $dentist->display_name])
                ->values(),
            'feeItems' => FeeItem::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'price_cents'])
                ->map(fn (FeeItem $item) => [
                    'id' => $item->id,
                    'label' => $item->name,
                    'price_cents' => $item->price_cents,
                ])
                ->values(),
            'canUpdateOdontogram' => (new ChartPolicy)->updateOdontogram($user, $patient),
            'canCreatePlan' => $user?->can('create', TreatmentPlan::class) ?? false,
        ]);
    }

    public function updateOdontogram(UpdateOdontogramRequest $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validated();
        $userId = $request->user()?->id;
        $toothFdi = $validated['tooth_fdi'];
        $newStatus = ToothStatus::from($validated['status']);
        /** @var list<string> $surfaces */
        $surfaces = $validated['surfaces'] ?? [];

        DB::transaction(function () use ($patient, $toothFdi, $newStatus, $surfaces, $validated, $userId): void {
            $encounterId = $validated['encounter_id'] ?? Encounter::query()
                ->where('patient_id', $patient->id)
                ->whereHas('soapNote', fn ($query) => $query->whereNull('signed_at'))
                ->latest('visited_at')
                ->value('id');

            $tooth = OdontogramTooth::query()->firstOrNew([
                'patient_id' => $patient->id,
                'tooth_fdi' => $toothFdi,
            ]);

            $previousStatus = $tooth->exists ? $tooth->status : null;

            $tooth->fill([
                'status' => $newStatus,
                'updated_by' => $userId,
            ]);

            if (! $tooth->exists) {
                $tooth->created_by = $userId;
            }

            $tooth->save();

            $tooth->surfaces()->delete();

            foreach ($surfaces as $surfaceValue) {
                OdontogramSurface::query()->create([
                    'odontogram_tooth_id' => $tooth->id,
                    'surface' => ToothSurface::from($surfaceValue),
                ]);
            }

            ToothHistory::query()->create([
                'patient_id' => $patient->id,
                'tooth_fdi' => $toothFdi,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'surfaces' => $surfaces,
                'notes' => $validated['notes'] ?? null,
                'encounter_id' => $encounterId,
                'recorded_by' => $userId,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Odontogram updated.')]);

        return to_route('patients.chart', $patient);
    }

    public function storePlan(StoreTreatmentPlanRequest $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validated();
        $userId = $request->user()?->id;

        TreatmentPlan::query()->create([
            'patient_id' => $patient->id,
            'dentist_id' => $validated['dentist_id'],
            'title' => $validated['title'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Treatment plan created.')]);

        return to_route('patients.chart', $patient);
    }
}
