<?php

namespace App\Http\Controllers;

use App\Enums\ImagingOrderStatus;
use App\Enums\ImagingOrderType;
use App\Enums\PatientStatus;
use App\Http\Requests\StoreImagingOrderRequest;
use App\Models\Dentist;
use App\Models\Encounter;
use App\Models\ImageFile;
use App\Models\ImagingOrder;
use App\Models\ImagingResult;
use App\Models\Patient;
use App\Services\ImagingOrderNumberGenerator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ImagingOrderController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ImagingOrder::class);

        $search = trim((string) $request->query('search', ''));

        $orders = ImagingOrder::query()
            ->with(['patient', 'dentist'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('number', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('patient', function ($query) use ($search): void {
                            $query->where('patient_number', 'like', "%{$search}%")
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ImagingOrder $order) => $this->listItem($order));

        return Inertia::render('imaging/Index', [
            'orders' => $orders,
            'search' => $search,
            'canCreate' => $request->user()?->can('create', ImagingOrder::class) ?? false,
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', ImagingOrder::class);

        $selectedPatientId = $request->integer('patient_id') ?: null;
        $selectedPatient = $selectedPatientId !== null
            ? Patient::query()
                ->where('status', PatientStatus::Active)
                ->find($selectedPatientId)
            : null;

        $user = $request->user();
        $user?->loadMissing('dentist');

        return Inertia::render('imaging/Create', [
            'types' => $this->typeOptions(),
            'statuses' => $this->statusOptions(),
            'dentists' => $this->dentistOptions(),
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
        StoreImagingOrderRequest $request,
        ImagingOrderNumberGenerator $numberGenerator,
    ): RedirectResponse {
        $userId = $request->user()?->id;
        $validated = $request->validated();
        $maxAttempts = 3;
        $order = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $order = DB::transaction(function () use ($request, $validated, $numberGenerator, $userId): ImagingOrder {
                    $order = ImagingOrder::query()->create([
                        'number' => $numberGenerator->generate(),
                        'patient_id' => $validated['patient_id'],
                        'dentist_id' => $validated['dentist_id'],
                        'encounter_id' => $validated['encounter_id'] ?? null,
                        'type' => $validated['type'],
                        'notes' => filled($validated['notes'] ?? null) ? $validated['notes'] : null,
                        'status' => $validated['status'] ?? ImagingOrderStatus::Ordered,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);

                    $hasResultMetadata = filled($validated['result_findings'] ?? null)
                        || filled($validated['result_impression'] ?? null);

                    if ($hasResultMetadata) {
                        ImagingResult::query()->create([
                            'imaging_order_id' => $order->id,
                            'findings' => filled($validated['result_findings'] ?? null) ? $validated['result_findings'] : null,
                            'impression' => filled($validated['result_impression'] ?? null) ? $validated['result_impression'] : null,
                            'reported_at' => now(),
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);
                    }

                    if ($request->hasFile('file')) {
                        $this->storeUploadedFile($request->file('file'), $order, $userId);
                    }

                    return $order;
                });

                break;
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === $maxAttempts) {
                    throw $exception;
                }
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Imaging order created.')]);

        return to_route('imaging.show', $order);
    }

    public function show(Request $request, ImagingOrder $imagingOrder): Response
    {
        Gate::authorize('view', $imagingOrder);

        $imagingOrder->load(['patient', 'dentist', 'encounter', 'result', 'files']);

        return Inertia::render('imaging/Show', [
            'order' => $this->detail($imagingOrder),
        ]);
    }

    private function storeUploadedFile(UploadedFile $file, ImagingOrder $order, ?int $userId): void
    {
        $path = $file->store("imaging/{$order->id}", 'local');

        ImageFile::query()->create([
            'imaging_order_id' => $order->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'created_by' => $userId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function listItem(ImagingOrder $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'type' => $order->type->value,
            'type_label' => $order->type->label(),
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'patient_name' => "{$order->patient->first_name} {$order->patient->last_name}",
            'patient_number' => $order->patient->patient_number,
            'dentist_name' => $order->dentist->display_name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(ImagingOrder $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'type' => $order->type->value,
            'type_label' => $order->type->label(),
            'notes' => $order->notes,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'created_at_formatted' => $order->created_at?->format('M j, Y g:i A'),
            'patient' => [
                'id' => $order->patient->id,
                'full_name' => "{$order->patient->first_name} {$order->patient->last_name}",
                'patient_number' => $order->patient->patient_number,
            ],
            'dentist_name' => $order->dentist->display_name,
            'encounter' => $order->encounter ? [
                'id' => $order->encounter->id,
                'number' => $order->encounter->number,
            ] : null,
            'result' => $order->result ? [
                'findings' => $order->result->findings,
                'impression' => $order->result->impression,
                'reported_at_formatted' => $order->result->reported_at?->format('M j, Y g:i A'),
            ] : null,
            'files' => $order->files->map(fn (ImageFile $file) => [
                'id' => $file->id,
                'original_name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'size_bytes' => $file->size_bytes,
                'size_formatted' => $this->formatFileSize($file->size_bytes),
                'exists_on_disk' => Storage::disk($file->disk)->exists($file->path),
            ])->values()->all(),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        return collect(ImagingOrderType::cases())
            ->map(fn (ImagingOrderType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return collect(ImagingOrderStatus::cases())
            ->map(fn (ImagingOrderStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->values()
            ->all();
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

    private function formatFileSize(?int $bytes): ?string
    {
        if ($bytes === null) {
            return null;
        }

        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 1).' MB';
    }
}
