<?php

namespace App\Http\Controllers;

use App\Enums\Gender;
use App\Enums\PatientStatus;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientCondition;
use App\Models\PatientMedication;
use App\Services\PatientNumberGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Patient::class);

        $search = trim((string) $request->query('q', ''));

        if ($search === '') {
            return response()->json(['patients' => []]);
        }

        $patients = Patient::query()
            ->where('status', '!=', PatientStatus::Archived)
            ->where(function ($query) use ($search): void {
                $this->applyPatientSearch($query, $search);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(15)
            ->get(['id', 'first_name', 'last_name', 'patient_number', 'phone'])
            ->map(fn (Patient $patient) => $this->patientSearchResult($patient))
            ->values()
            ->all();

        return response()->json(['patients' => $patients]);
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Patient::class);

        $search = trim((string) $request->query('search', ''));

        $patients = Patient::withTrashed()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $this->applyPatientSearch($query, $search);
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Patient $patient) => $this->patientListItem($patient));

        return Inertia::render('patients/Index', [
            'patients' => $patients,
            'search' => $search,
            'canCreate' => $request->user()?->can('create', Patient::class) ?? false,
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Patient::class);

        return Inertia::render('patients/Create', [
            'genders' => $this->genderOptions(),
        ]);
    }

    public function store(StorePatientRequest $request, PatientNumberGenerator $numberGenerator): RedirectResponse
    {
        $patient = DB::transaction(function () use ($request, $numberGenerator): Patient {
            $userId = $request->user()?->id;

            $patient = Patient::query()->create([
                'patient_number' => $numberGenerator->generate(),
                'first_name' => $request->string('first_name')->trim()->value(),
                'last_name' => $request->string('last_name')->trim()->value(),
                'date_of_birth' => $request->date('date_of_birth'),
                'gender' => $request->enum('gender', Gender::class),
                'phone' => $request->string('phone')->value(),
                'email' => $request->string('email')->toString() ?: null,
                'occupation' => $request->string('occupation')->toString() ?: null,
                'address' => $request->string('address')->toString() ?: null,
                'status' => PatientStatus::Active,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->syncNestedData($patient, $request->validated(), $userId);

            return $patient;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Patient registered.')]);

        return to_route('patients.show', $patient);
    }

    public function show(Request $request, Patient $patient): Response
    {
        Gate::authorize('view', $patient);

        $patient->load([
            'allergies',
            'conditions',
            'medications',
            'emergencyContacts',
            'treatments' => fn ($query) => $query->orderByDesc('diagnosed_at'),
        ]);

        AuditLog::query()->create([
            'action' => 'patient.viewed',
            'auditable_type' => Patient::class,
            'auditable_id' => $patient->id,
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ]);

        return Inertia::render('patients/Show', [
            'patient' => $this->patientDetail($patient),
            'canUpdate' => $request->user()?->can('update', $patient) ?? false,
            'canArchive' => $request->user()?->can('archive', $patient) ?? false,
        ]);
    }

    public function edit(Request $request, Patient $patient): Response
    {
        Gate::authorize('update', $patient);

        $patient->load(['allergies', 'conditions', 'medications', 'emergencyContacts']);

        return Inertia::render('patients/Edit', [
            'patient' => $this->patientDetail($patient),
            'genders' => $this->genderOptions(),
        ]);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('update', $patient);

        DB::transaction(function () use ($request, $patient): void {
            $userId = $request->user()?->id;

            $patient->update([
                'first_name' => $request->string('first_name')->trim()->value(),
                'last_name' => $request->string('last_name')->trim()->value(),
                'date_of_birth' => $request->date('date_of_birth'),
                'gender' => $request->enum('gender', Gender::class),
                'phone' => $request->string('phone')->value(),
                'email' => $request->string('email')->toString() ?: null,
                'occupation' => $request->string('occupation')->toString() ?: null,
                'address' => $request->string('address')->toString() ?: null,
                'updated_by' => $userId,
            ]);

            $this->syncNestedData($patient, $request->validated(), $userId);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Patient updated.')]);

        return to_route('patients.show', $patient);
    }

    public function archive(Request $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('archive', $patient);

        $patient->update([
            'status' => PatientStatus::Archived,
            'updated_by' => $request->user()?->id,
        ]);

        $patient->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Patient archived.')]);

        return to_route('patients.show', $patient);
    }

    /**
     * @param  Builder<Patient>  $query
     */
    private function applyPatientSearch($query, string $search): void
    {
        $query->where('patient_number', 'like', "%{$search}%")
            ->orWhere('phone', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
            ->orWhere('first_name', 'like', "%{$search}%")
            ->orWhere('last_name', 'like', "%{$search}%");
    }

    /**
     * @return array{id: int, label: string, patient_number: string, full_name: string, phone: string}
     */
    private function patientSearchResult(Patient $patient): array
    {
        return [
            'id' => $patient->id,
            'label' => "{$patient->first_name} {$patient->last_name} ({$patient->patient_number})",
            'patient_number' => $patient->patient_number,
            'full_name' => "{$patient->first_name} {$patient->last_name}",
            'phone' => $patient->phone,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function genderOptions(): array
    {
        return collect(Gender::cases())
            ->map(fn (Gender $gender) => [
                'value' => $gender->value,
                'label' => ucfirst(str_replace('_', ' ', $gender->value)),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function patientListItem(Patient $patient): array
    {
        return [
            'id' => $patient->id,
            'patient_number' => $patient->patient_number,
            'full_name' => "{$patient->first_name} {$patient->last_name}",
            'phone' => $patient->phone,
            'email' => $patient->email,
            'status' => $patient->status->value,
            'is_archived' => $patient->trashed() || $patient->status === PatientStatus::Archived,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function patientDetail(Patient $patient): array
    {
        $emergencyContact = $patient->emergencyContacts->first();

        return [
            'id' => $patient->id,
            'patient_number' => $patient->patient_number,
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'full_name' => "{$patient->first_name} {$patient->last_name}",
            'date_of_birth' => $patient->date_of_birth->toDateString(),
            'gender' => $patient->gender->value,
            'phone' => $patient->phone,
            'email' => $patient->email,
            'occupation' => $patient->occupation,
            'address' => $patient->address,
            'status' => $patient->status->value,
            'is_archived' => $patient->trashed() || $patient->status === PatientStatus::Archived,
            'allergies' => $patient->allergies->map(fn ($item) => [
                'id' => $item->id,
                'label' => $item->label,
                'is_critical' => $item->is_critical,
            ])->values(),
            'conditions' => $patient->conditions->map(fn ($item) => [
                'id' => $item->id,
                'label' => $item->label,
                'is_critical' => $item->is_critical,
            ])->values(),
            'medications' => $patient->medications->map(fn ($item) => [
                'id' => $item->id,
                'label' => $item->label,
                'is_critical' => $item->is_critical,
            ])->values(),
            'emergency_contact' => $emergencyContact ? [
                'name' => $emergencyContact->name,
                'relationship' => $emergencyContact->relationship,
                'phone' => $emergencyContact->phone,
            ] : null,
            'treatments' => $patient->treatments->map(fn ($treatment) => [
                'id' => $treatment->id,
                'diagnosis' => $treatment->diagnosis,
                'status' => $treatment->status->value,
                'status_label' => ucfirst(str_replace('_', ' ', $treatment->status->value)),
                'diagnosed_at' => $treatment->diagnosed_at->toDateString(),
                'diagnosed_at_formatted' => $treatment->diagnosed_at->format('M j, Y'),
            ])->values(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncNestedData(Patient $patient, array $data, ?int $userId): void
    {
        if (array_key_exists('allergies', $data)) {
            $this->syncMedicalCollection($patient->allergies(), $data['allergies'], $userId);
        }

        if (array_key_exists('conditions', $data)) {
            $this->syncMedicalCollection($patient->conditions(), $data['conditions'], $userId);
        }

        if (array_key_exists('medications', $data)) {
            $this->syncMedicalCollection($patient->medications(), $data['medications'], $userId);
        }

        if (array_key_exists('emergency_contact', $data)) {
            $this->syncEmergencyContact($patient, $data['emergency_contact'], $userId);
        }
    }

    /**
     * @param  HasMany<PatientAllergy|PatientCondition|PatientMedication, Patient>  $relation
     * @param  list<array<string, mixed>>  $items
     */
    private function syncMedicalCollection(HasMany $relation, array $items, ?int $userId): void
    {
        $keptIds = [];
        $model = $relation->getRelated();
        $foreignKey = $relation->getForeignKeyName();
        $parentKey = $relation->getParentKey();

        foreach ($items as $item) {
            if (blank($item['label'] ?? null)) {
                continue;
            }

            $attributes = [
                'label' => $item['label'],
                'is_critical' => (bool) ($item['is_critical'] ?? false),
                'updated_by' => $userId,
            ];

            if (! empty($item['id'])) {
                $existing = $model->newQuery()
                    ->where($foreignKey, $parentKey)
                    ->whereKey($item['id'])
                    ->first();

                if ($existing !== null) {
                    $existing->update($attributes);
                    $keptIds[] = $existing->id;

                    continue;
                }
            }

            $created = $model->newQuery()->create(array_merge($attributes, [
                $foreignKey => $parentKey,
                'created_by' => $userId,
            ]));

            $keptIds[] = $created->id;
        }

        $scopedQuery = $model->newQuery()->where($foreignKey, $parentKey);

        if ($keptIds === []) {
            $scopedQuery->delete();

            return;
        }

        $scopedQuery->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * @param  array<string, mixed>|null  $emergencyContact
     */
    private function syncEmergencyContact(Patient $patient, mixed $emergencyContact, ?int $userId): void
    {
        $patient->emergencyContacts()->delete();

        if (! is_array($emergencyContact)) {
            return;
        }

        if (filled($emergencyContact['name'] ?? null) && filled($emergencyContact['phone'] ?? null)) {
            $patient->emergencyContacts()->create([
                'name' => $emergencyContact['name'],
                'relationship' => $emergencyContact['relationship'] ?? null,
                'phone' => $emergencyContact['phone'],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }
    }
}
