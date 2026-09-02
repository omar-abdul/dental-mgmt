<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\ClinicRole;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\AppointmentRevision;
use App\Models\Chair;
use App\Models\Dentist;
use App\Models\FeeItem;
use App\Models\Patient;
use App\Models\WorkingHour;
use App\Services\AppointmentNumberGenerator;
use App\Services\AppointmentScheduler;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Appointment::class);

        $date = $this->resolveDate($request);
        $workingHour = $this->workingHourForDate($date);

        $columns = Dentist::query()
            ->where('is_active', true)
            ->with(['defaultChair.room'])
            ->orderBy('display_name')
            ->get()
            ->map(fn (Dentist $dentist) => $this->columnItem($dentist))
            ->values()
            ->all();

        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        $appointments = Appointment::query()
            ->with(['patient', 'feeItem'])
            ->whereBetween('starts_at', [$dayStart, $dayEnd])
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Appointment $appointment) => $this->appointmentItem($appointment))
            ->values()
            ->all();

        return Inertia::render('appointments/Index', [
            'date' => $date->toDateString(),
            'workingHours' => $this->workingHoursPayload($workingHour),
            'columns' => $columns,
            'appointments' => $appointments,
            'patients' => $this->patientOptions(),
            'dentists' => $this->dentistOptions(),
            'chairs' => $this->chairOptions(),
            'feeItems' => $this->feeItemOptions(),
            'statuses' => $this->statusOptions(),
        ] + $this->capabilityFlags($request));
    }

    public function store(
        StoreAppointmentRequest $request,
        AppointmentNumberGenerator $numberGenerator,
        AppointmentScheduler $scheduler,
    ): RedirectResponse {
        $feeItem = $this->resolveFeeItem($request->integer('fee_item_id') ?: null);
        $startsAt = Carbon::parse($request->string('starts_at')->value());
        $endsAt = $scheduler->calculateEndsAt(
            $startsAt,
            $feeItem,
            $request->integer('duration_minutes') ?: null,
        );

        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                DB::transaction(function () use ($request, $numberGenerator, $scheduler, $feeItem, $startsAt, $endsAt): void {
                    $scheduler->validateWorkingHours($startsAt, $endsAt);
                    $scheduler->assertNoOverlap(
                        $request->integer('dentist_id'),
                        $request->integer('chair_id'),
                        $startsAt,
                        $endsAt,
                    );

                    $userId = $request->user()?->id;

                    Appointment::query()->create([
                        'number' => $numberGenerator->generate(),
                        'patient_id' => $request->integer('patient_id'),
                        'dentist_id' => $request->integer('dentist_id'),
                        'chair_id' => $request->integer('chair_id'),
                        'fee_item_id' => $feeItem?->id,
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                        'status' => AppointmentStatus::Scheduled,
                        'reason' => $request->string('reason')->toString() ?: null,
                        'notes' => $request->string('notes')->toString() ?: null,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                });

                break;
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === $maxAttempts) {
                    throw $exception;
                }
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Appointment booked.')]);

        return to_route('appointments.index', ['date' => $startsAt->toDateString()]);
    }

    public function update(
        UpdateAppointmentRequest $request,
        Appointment $appointment,
        AppointmentScheduler $scheduler,
    ): RedirectResponse {
        Gate::authorize('update', $appointment);

        $feeItem = $this->resolveFeeItem(
            $request->has('fee_item_id')
                ? ($request->integer('fee_item_id') ?: null)
                : $appointment->fee_item_id,
        );

        $startsAt = $request->has('starts_at')
            ? Carbon::parse($request->string('starts_at')->value())
            : $appointment->starts_at->copy();

        $durationMinutes = $request->has('duration_minutes')
            ? ($request->integer('duration_minutes') ?: null)
            : (int) $appointment->starts_at->diffInMinutes($appointment->ends_at);

        $endsAt = $request->has('starts_at') || $request->has('duration_minutes') || $request->has('fee_item_id')
            ? $scheduler->calculateEndsAt($startsAt, $feeItem, $durationMinutes)
            : $appointment->ends_at->copy();

        $timesChanged = ! $startsAt->equalTo($appointment->starts_at) || ! $endsAt->equalTo($appointment->ends_at);

        DB::transaction(function () use ($request, $appointment, $scheduler, $feeItem, $startsAt, $endsAt, $timesChanged): void {
            $dentistId = $request->integer('dentist_id', $appointment->dentist_id);
            $chairId = $request->integer('chair_id', $appointment->chair_id);

            if ($timesChanged) {
                $scheduler->validateWorkingHours($startsAt, $endsAt);
            }

            $scheduler->assertNoOverlap(
                $dentistId,
                $chairId,
                $startsAt,
                $endsAt,
                $appointment->id,
            );

            $userId = $request->user()?->id;

            if ($timesChanged) {
                AppointmentRevision::query()->create([
                    'appointment_id' => $appointment->id,
                    'previous_starts_at' => $appointment->starts_at,
                    'previous_ends_at' => $appointment->ends_at,
                    'action' => 'reschedule',
                    'created_by' => $userId,
                ]);
            }

            $appointment->update([
                'patient_id' => $request->integer('patient_id', $appointment->patient_id),
                'dentist_id' => $dentistId,
                'chair_id' => $chairId,
                'fee_item_id' => $feeItem?->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $timesChanged ? AppointmentStatus::Rescheduled : $appointment->status,
                'reason' => $request->has('reason')
                    ? ($request->string('reason')->toString() ?: null)
                    : $appointment->reason,
                'notes' => $request->has('notes')
                    ? ($request->string('notes')->toString() ?: null)
                    : $appointment->notes,
                'updated_by' => $userId,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Appointment updated.')]);

        return to_route('appointments.index', ['date' => $startsAt->toDateString()]);
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        Gate::authorize('cancel', $appointment);

        DB::transaction(function () use ($request, $appointment): void {
            $userId = $request->user()?->id;

            AppointmentRevision::query()->create([
                'appointment_id' => $appointment->id,
                'previous_starts_at' => $appointment->starts_at,
                'previous_ends_at' => $appointment->ends_at,
                'action' => 'cancel',
                'created_by' => $userId,
            ]);

            $appointment->update([
                'status' => AppointmentStatus::Cancelled,
                'updated_by' => $userId,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Appointment cancelled.')]);

        return to_route('appointments.index', ['date' => $appointment->starts_at->toDateString()]);
    }

    public function checkIn(Request $request, Appointment $appointment): RedirectResponse
    {
        Gate::authorize('checkIn', $appointment);

        if (! in_array($appointment->status, [
            AppointmentStatus::Scheduled,
            AppointmentStatus::Confirmed,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => __('This appointment cannot be checked in.'),
            ]);
        }

        $appointment->update([
            'status' => AppointmentStatus::CheckedIn,
            'updated_by' => $request->user()?->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Patient checked in.')]);

        return to_route('appointments.index', ['date' => $appointment->starts_at->toDateString()]);
    }

    private function resolveDate(Request $request): Carbon
    {
        $dateInput = trim((string) $request->query('date', ''));

        if ($dateInput !== '') {
            return Carbon::parse($dateInput)->startOfDay();
        }

        return Carbon::parse(now()->toDateString())->startOfDay();
    }

    private function workingHourForDate(Carbon $date): ?WorkingHour
    {
        return WorkingHour::query()
            ->where('weekday', $date->dayOfWeek)
            ->first();
    }

    /**
     * @return array{weekday: int, opens_at: string|null, closes_at: string|null, is_closed: bool}
     */
    private function workingHoursPayload(?WorkingHour $workingHour): array
    {
        if ($workingHour === null) {
            return [
                'weekday' => 0,
                'opens_at' => null,
                'closes_at' => null,
                'is_closed' => true,
            ];
        }

        $isClosed = $workingHour->opens_at === null || $workingHour->closes_at === null;

        return [
            'weekday' => $workingHour->weekday,
            'opens_at' => $isClosed ? null : substr($workingHour->opens_at, 0, 5),
            'closes_at' => $isClosed ? null : substr($workingHour->closes_at, 0, 5),
            'is_closed' => $isClosed,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function columnItem(Dentist $dentist): array
    {
        $roomName = $dentist->defaultChair?->room?->name;

        return [
            'id' => $dentist->id,
            'label' => $roomName
                ? "{$dentist->display_name} — {$roomName}"
                : $dentist->display_name,
            'dentist_name' => $dentist->display_name,
            'room_name' => $roomName,
            'default_chair_id' => $dentist->default_chair_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function appointmentItem(Appointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'number' => $appointment->number,
            'dentist_id' => $appointment->dentist_id,
            'chair_id' => $appointment->chair_id,
            'patient_id' => $appointment->patient_id,
            'fee_item_id' => $appointment->fee_item_id,
            'patient_name' => "{$appointment->patient->first_name} {$appointment->patient->last_name}",
            'fee_name' => $appointment->feeItem?->name,
            'calendar_color' => $appointment->feeItem?->calendar_color ?? '#64748b',
            'starts_at' => $appointment->starts_at->toIso8601String(),
            'ends_at' => $appointment->ends_at->toIso8601String(),
            'starts_at_time' => $appointment->starts_at->format('H:i'),
            'ends_at_time' => $appointment->ends_at->format('H:i'),
            'status' => $appointment->status->value,
            'reason' => $appointment->reason,
            'notes' => $appointment->notes,
            'can_update' => auth()->user()?->can('update', $appointment) ?? false,
            'can_cancel' => auth()->user()?->can('cancel', $appointment) ?? false,
            'can_check_in' => auth()->user()?->can('checkIn', $appointment) ?? false,
        ];
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function patientOptions(): array
    {
        return Patient::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(200)
            ->get(['id', 'first_name', 'last_name', 'patient_number'])
            ->map(fn (Patient $patient) => [
                'id' => $patient->id,
                'label' => "{$patient->first_name} {$patient->last_name} ({$patient->patient_number})",
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, label: string, default_chair_id: int|null}>
     */
    private function dentistOptions(): array
    {
        return Dentist::query()
            ->where('is_active', true)
            ->orderBy('display_name')
            ->get(['id', 'display_name', 'default_chair_id'])
            ->map(fn (Dentist $dentist) => [
                'id' => $dentist->id,
                'label' => $dentist->display_name,
                'default_chair_id' => $dentist->default_chair_id,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function chairOptions(): array
    {
        return Chair::query()
            ->where('is_active', true)
            ->with('room')
            ->orderBy('name')
            ->get()
            ->map(fn (Chair $chair) => [
                'id' => $chair->id,
                'label' => $chair->room
                    ? "{$chair->name} ({$chair->room->name})"
                    : $chair->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, label: string, default_duration_minutes: int, calendar_color: string}>
     */
    private function feeItemOptions(): array
    {
        return FeeItem::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'default_duration_minutes', 'calendar_color'])
            ->map(fn (FeeItem $feeItem) => [
                'id' => $feeItem->id,
                'label' => $feeItem->name,
                'default_duration_minutes' => $feeItem->default_duration_minutes,
                'calendar_color' => $feeItem->calendar_color,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return collect(AppointmentStatus::cases())
            ->map(fn (AppointmentStatus $status) => [
                'value' => $status->value,
                'label' => ucfirst(str_replace('_', ' ', $status->value)),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{canBook: bool, canCheckIn: bool}
     */
    private function capabilityFlags(Request $request): array
    {
        return [
            'canBook' => $request->user()?->can('create', Appointment::class) ?? false,
            'canCheckIn' => $request->user()?->hasRole(
                ClinicRole::Admin,
                ClinicRole::Receptionist,
                ClinicRole::Nurse,
            ) ?? false,
        ];
    }

    private function resolveFeeItem(?int $feeItemId): ?FeeItem
    {
        if ($feeItemId === null) {
            return null;
        }

        return FeeItem::query()->find($feeItemId);
    }
}
