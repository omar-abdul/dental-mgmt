<?php

namespace App\Http\Controllers;

use App\Enums\LabOrderStatus;
use App\Enums\PatientStatus;
use App\Http\Requests\StoreLabOrderRequest;
use App\Http\Requests\UpdateLabOrderStatusRequest;
use App\Models\Dentist;
use App\Models\Encounter;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Treatment;
use App\Services\LabOrderNumberGenerator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LabOrderController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', LabOrder::class);

        $search = trim((string) $request->query('search', ''));

        $orders = LabOrder::query()
            ->with(['patient', 'dentist'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('patient', function ($query) use ($search): void {
                            $query->where('patient_number', 'like', "%{$search}%")
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (LabOrder $order) => $this->listItem($order));

        return Inertia::render('lab/Index', [
            'orders' => $orders,
            'search' => $search,
            'canCreate' => $request->user()?->can('create', LabOrder::class) ?? false,
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', LabOrder::class);

        $selectedPatientId = $request->integer('patient_id') ?: null;
        $selectedPatient = $selectedPatientId !== null
            ? Patient::query()
                ->where('status', PatientStatus::Active)
                ->find($selectedPatientId)
            : null;

        $user = $request->user();
        $user?->loadMissing('dentist');

        return Inertia::render('lab/Create', [
            'dentists' => $this->dentistOptions(),
            'treatments' => $this->treatmentOptions($selectedPatientId),
            'encounters' => $this->encounterOptions($selectedPatientId),
            'defaultDentistId' => $user?->dentist?->id,
            'selectedPatientId' => $selectedPatientId,
            'selectedPatient' => $selectedPatient !== null ? [
                'id' => $selectedPatient->id,
                'label' => "{$selectedPatient->first_name} {$selectedPatient->last_name} ({$selectedPatient->patient_number})",
                'patient_number' => $selectedPatient->patient_number,
                'full_name' => "{$selectedPatient->first_name} {$selectedPatient->last_name}",
            ] : null,
        ]);
    }

    public function store(
        StoreLabOrderRequest $request,
        LabOrderNumberGenerator $numberGenerator,
    ): RedirectResponse {
        $userId = $request->user()?->id;
        $validated = $request->validated();
        $maxAttempts = 3;
        $order = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $order = LabOrder::query()->create([
                    'number' => $numberGenerator->generate(),
                    'patient_id' => $validated['patient_id'],
                    'dentist_id' => $validated['dentist_id'],
                    'treatment_id' => $validated['treatment_id'] ?? null,
                    'encounter_id' => $validated['encounter_id'] ?? null,
                    'description' => trim($validated['description']),
                    'notes' => filled($validated['notes'] ?? null) ? $validated['notes'] : null,
                    'due_date' => $validated['due_date'] ?? null,
                    'status' => LabOrderStatus::Ordered,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                break;
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === $maxAttempts) {
                    throw $exception;
                }
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Lab order created.')]);

        return to_route('lab.show', $order);
    }

    public function show(Request $request, LabOrder $labOrder): Response
    {
        Gate::authorize('view', $labOrder);

        $labOrder->load(['patient', 'dentist', 'treatment', 'encounter']);

        return Inertia::render('lab/Show', [
            'order' => $this->detail($labOrder),
            'canTransition' => $request->user()?->can('transition', $labOrder) ?? false,
        ]);
    }

    public function transition(UpdateLabOrderStatusRequest $request, LabOrder $labOrder): RedirectResponse
    {
        $status = $request->enum('status', LabOrderStatus::class);

        DB::transaction(function () use ($request, $labOrder, $status): void {
            $labOrder->update([
                'status' => $status,
                'updated_by' => $request->user()?->id,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Lab order status updated.')]);

        return to_route('lab.show', $labOrder);
    }

    /**
     * @return array<string, mixed>
     */
    private function listItem(LabOrder $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'description' => $order->description,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'due_date' => $order->due_date?->toDateString(),
            'due_date_formatted' => $order->due_date?->format('M j, Y'),
            'patient_name' => "{$order->patient->first_name} {$order->patient->last_name}",
            'patient_number' => $order->patient->patient_number,
            'dentist_name' => $order->dentist->display_name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(LabOrder $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'description' => $order->description,
            'notes' => $order->notes,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'due_date' => $order->due_date?->toDateString(),
            'due_date_formatted' => $order->due_date?->format('M j, Y'),
            'created_at_formatted' => $order->created_at?->format('M j, Y g:i A'),
            'patient' => [
                'id' => $order->patient->id,
                'full_name' => "{$order->patient->first_name} {$order->patient->last_name}",
                'patient_number' => $order->patient->patient_number,
            ],
            'dentist_name' => $order->dentist->display_name,
            'treatment' => $order->treatment ? [
                'id' => $order->treatment->id,
                'diagnosis' => $order->treatment->diagnosis,
            ] : null,
            'encounter' => $order->encounter ? [
                'id' => $order->encounter->id,
                'number' => $order->encounter->number,
            ] : null,
            'next_statuses' => collect($order->status->allowedTransitions())
                ->map(fn (LabOrderStatus $status) => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->values()
                ->all(),
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
     * @return list<array{id: int, label: string}>
     */
    private function treatmentOptions(?int $patientId): array
    {
        if ($patientId === null) {
            return [];
        }

        return Treatment::query()
            ->where('patient_id', $patientId)
            ->orderByDesc('diagnosed_at')
            ->limit(50)
            ->get(['id', 'diagnosis', 'diagnosed_at'])
            ->map(fn (Treatment $treatment) => [
                'id' => $treatment->id,
                'label' => "{$treatment->diagnosis} — {$treatment->diagnosed_at->format('M j, Y')}",
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function encounterOptions(?int $patientId): array
    {
        if ($patientId === null) {
            return [];
        }

        return Encounter::query()
            ->where('patient_id', $patientId)
            ->orderByDesc('visited_at')
            ->limit(50)
            ->get(['id', 'number', 'visited_at'])
            ->map(fn (Encounter $encounter) => [
                'id' => $encounter->id,
                'label' => "{$encounter->number} — {$encounter->visited_at->format('M j, Y g:i A')}",
            ])
            ->values()
            ->all();
    }
}
