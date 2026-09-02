<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\TreatmentStatus;
use App\Http\Requests\StoreTreatmentRequest;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\FeeItem;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Treatment;
use App\Models\TreatmentProcedure;
use App\Services\PrescriptionNumberGenerator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TreatmentController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Treatment::class);

        $search = trim((string) $request->query('search', ''));

        $treatments = Treatment::query()
            ->with(['patient', 'dentist'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('diagnosis', 'like', "%{$search}%")
                        ->orWhereHas('patient', function ($query) use ($search): void {
                            $query->where('patient_number', 'like', "%{$search}%")
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                        });
                });
            })
            ->orderByDesc('diagnosed_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Treatment $treatment) => $this->treatmentListItem($treatment));

        return Inertia::render('treatments/Index', [
            'treatments' => $treatments,
            'search' => $search,
            'canCreate' => $request->user()?->can('create', Treatment::class) ?? false,
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Treatment::class);

        $selectedPatientId = $request->integer('patient_id') ?: null;
        $selectedPatient = $selectedPatientId !== null
            ? Patient::query()
                ->with(['allergies', 'conditions', 'medications'])
                ->find($selectedPatientId)
            : null;

        $user = $request->user();
        $user?->loadMissing('dentist');

        return Inertia::render('treatments/Create', [
            'dentists' => $this->dentistOptions(),
            'feeItems' => $this->feeItemOptions(),
            'appointments' => $this->appointmentOptions($selectedPatientId),
            'statuses' => $this->statusOptions(),
            'defaultDentistId' => $user?->dentist?->id,
            'selectedPatientId' => $selectedPatientId,
            'selectedPatient' => $selectedPatient !== null ? [
                'id' => $selectedPatient->id,
                'label' => "{$selectedPatient->first_name} {$selectedPatient->last_name} ({$selectedPatient->patient_number})",
                'patient_number' => $selectedPatient->patient_number,
                'full_name' => "{$selectedPatient->first_name} {$selectedPatient->last_name}",
                'phone' => $selectedPatient->phone,
            ] : null,
            'criticalAlerts' => $this->criticalAlerts($selectedPatient),
        ]);
    }

    public function store(
        StoreTreatmentRequest $request,
        PrescriptionNumberGenerator $numberGenerator,
    ): RedirectResponse {
        $status = $request->enum('status', TreatmentStatus::class) ?? TreatmentStatus::Planned;
        $diagnosedAt = $request->date('diagnosed_at') ?? now();
        $userId = $request->user()?->id;
        $validated = $request->validated();
        $maxAttempts = 3;
        $treatment = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $treatment = DB::transaction(function () use ($validated, $numberGenerator, $status, $diagnosedAt, $userId): Treatment {
                    $treatment = Treatment::query()->create([
                        'patient_id' => $validated['patient_id'],
                        'dentist_id' => $validated['dentist_id'],
                        'appointment_id' => $validated['appointment_id'] ?? null,
                        'diagnosed_at' => $diagnosedAt,
                        'diagnosis' => trim($validated['diagnosis']),
                        'status' => $status,
                        'notes' => filled($validated['notes'] ?? null) ? $validated['notes'] : null,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);

                    foreach ($validated['procedures'] as $procedure) {
                        $feeItem = FeeItem::query()->findOrFail($procedure['fee_item_id']);
                        $quantity = (int) $procedure['quantity'];

                        TreatmentProcedure::query()->create([
                            'treatment_id' => $treatment->id,
                            'fee_item_id' => $feeItem->id,
                            'tooth_fdi' => filled($procedure['tooth_fdi'] ?? null)
                                ? (string) $procedure['tooth_fdi']
                                : null,
                            'quantity' => $quantity,
                            'fee_cents' => $feeItem->price_cents * $quantity,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);
                    }

                    $prescription = Prescription::query()->create([
                        'number' => $numberGenerator->generate(),
                        'treatment_id' => $treatment->id,
                        'patient_id' => $treatment->patient_id,
                        'prescriber_id' => $userId,
                        'prescribed_at' => $diagnosedAt,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);

                    foreach ($validated['prescription_items'] as $item) {
                        PrescriptionItem::query()->create([
                            'prescription_id' => $prescription->id,
                            'medication' => $item['medication'],
                            'dosage' => $item['dosage'],
                            'instructions' => $item['instructions'],
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);
                    }

                    if ($status === TreatmentStatus::Completed) {
                        $this->completeLinkedAppointment($treatment, $userId);
                    }

                    return $treatment;
                });

                break;
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === $maxAttempts) {
                    throw $exception;
                }
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Treatment recorded.')]);

        return to_route('treatments.show', $treatment);
    }

    public function show(Request $request, Treatment $treatment): Response
    {
        Gate::authorize('view', $treatment);

        $treatment->load([
            'patient',
            'dentist',
            'appointment',
            'procedures.feeItem',
            'prescription.items',
            'prescription.prescriber',
        ]);

        return Inertia::render('treatments/Show', [
            'treatment' => $this->treatmentDetail($treatment),
            'canComplete' => $request->user()?->can('complete', $treatment) ?? false,
        ]);
    }

    public function complete(Request $request, Treatment $treatment): RedirectResponse
    {
        Gate::authorize('complete', $treatment);

        DB::transaction(function () use ($request, $treatment): void {
            $userId = $request->user()?->id;

            $treatment->update([
                'status' => TreatmentStatus::Completed,
                'updated_by' => $userId,
            ]);

            $this->completeLinkedAppointment($treatment, $userId);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Treatment completed.')]);

        return to_route('treatments.show', $treatment);
    }

    private function completeLinkedAppointment(Treatment $treatment, ?int $userId): void
    {
        if ($treatment->appointment_id === null) {
            return;
        }

        $appointment = Appointment::query()->find($treatment->appointment_id);

        if ($appointment === null) {
            return;
        }

        if (in_array($appointment->status, [
            AppointmentStatus::Cancelled,
            AppointmentStatus::NoShow,
            AppointmentStatus::Completed,
        ], true)) {
            return;
        }

        $appointment->update([
            'status' => AppointmentStatus::Completed,
            'updated_by' => $userId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function treatmentListItem(Treatment $treatment): array
    {
        return [
            'id' => $treatment->id,
            'diagnosis' => $treatment->diagnosis,
            'status' => $treatment->status->value,
            'status_label' => $this->statusLabel($treatment->status),
            'diagnosed_at' => $treatment->diagnosed_at->toDateString(),
            'diagnosed_at_formatted' => $treatment->diagnosed_at->format('M j, Y'),
            'patient_name' => "{$treatment->patient->first_name} {$treatment->patient->last_name}",
            'patient_number' => $treatment->patient->patient_number,
            'dentist_name' => $treatment->dentist->display_name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function treatmentDetail(Treatment $treatment): array
    {
        return [
            'id' => $treatment->id,
            'diagnosis' => $treatment->diagnosis,
            'status' => $treatment->status->value,
            'status_label' => $this->statusLabel($treatment->status),
            'diagnosed_at' => $treatment->diagnosed_at->toDateString(),
            'diagnosed_at_formatted' => $treatment->diagnosed_at->format('M j, Y g:i A'),
            'notes' => $treatment->notes,
            'patient' => [
                'id' => $treatment->patient->id,
                'full_name' => "{$treatment->patient->first_name} {$treatment->patient->last_name}",
                'patient_number' => $treatment->patient->patient_number,
            ],
            'dentist_name' => $treatment->dentist->display_name,
            'appointment' => $treatment->appointment ? [
                'id' => $treatment->appointment->id,
                'number' => $treatment->appointment->number,
            ] : null,
            'procedures' => $treatment->procedures->map(fn (TreatmentProcedure $procedure) => [
                'id' => $procedure->id,
                'fee_name' => $procedure->feeItem->name,
                'tooth_fdi' => $procedure->tooth_fdi,
                'quantity' => $procedure->quantity,
                'fee_cents' => $procedure->fee_cents,
                'fee_formatted' => $this->formatCents($procedure->fee_cents),
            ])->values(),
            'prescription' => $treatment->prescription ? [
                'number' => $treatment->prescription->number,
                'prescriber_name' => $treatment->prescription->prescriber->name,
                'prescribed_at_formatted' => $treatment->prescription->prescribed_at->format('M j, Y g:i A'),
                'items' => $treatment->prescription->items->map(fn (PrescriptionItem $item) => [
                    'medication' => $item->medication,
                    'dosage' => $item->dosage,
                    'instructions' => $item->instructions,
                ])->values(),
            ] : null,
        ];
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function dentistOptions(): array
    {
        return Dentist::query()
            ->where('is_active', true)
            ->orderBy('display_name')
            ->get(['id', 'display_name'])
            ->map(fn (Dentist $dentist) => [
                'id' => $dentist->id,
                'label' => $dentist->display_name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, label: string, price_cents: int}>
     */
    private function feeItemOptions(): array
    {
        return FeeItem::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'price_cents'])
            ->map(fn (FeeItem $feeItem) => [
                'id' => $feeItem->id,
                'label' => $feeItem->name,
                'price_cents' => $feeItem->price_cents,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function appointmentOptions(?int $patientId): array
    {
        if ($patientId === null) {
            return [];
        }

        return Appointment::query()
            ->where('patient_id', $patientId)
            ->whereDoesntHave('treatment')
            ->whereNotIn('status', [
                AppointmentStatus::Cancelled,
                AppointmentStatus::NoShow,
            ])
            ->orderByDesc('starts_at')
            ->limit(50)
            ->get(['id', 'number', 'starts_at'])
            ->map(fn (Appointment $appointment) => [
                'id' => $appointment->id,
                'label' => "{$appointment->number} — {$appointment->starts_at->format('M j, Y g:i A')}",
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return collect(TreatmentStatus::cases())
            ->map(fn (TreatmentStatus $status) => [
                'value' => $status->value,
                'label' => $this->statusLabel($status),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{allergies: list<array{label: string}>, conditions: list<array{label: string}>, medications: list<array{label: string}>}
     */
    private function criticalAlerts(?Patient $patient): array
    {
        if ($patient === null) {
            return [
                'allergies' => [],
                'conditions' => [],
                'medications' => [],
            ];
        }

        return [
            'allergies' => $patient->allergies
                ->where('is_critical', true)
                ->map(fn ($item) => ['label' => $item->label])
                ->values()
                ->all(),
            'conditions' => $patient->conditions
                ->where('is_critical', true)
                ->map(fn ($item) => ['label' => $item->label])
                ->values()
                ->all(),
            'medications' => $patient->medications
                ->where('is_critical', true)
                ->map(fn ($item) => ['label' => $item->label])
                ->values()
                ->all(),
        ];
    }

    private function statusLabel(TreatmentStatus $status): string
    {
        return ucfirst(str_replace('_', ' ', $status->value));
    }

    private function formatCents(int $cents): string
    {
        return '$'.number_format($cents / 100, 2);
    }
}
