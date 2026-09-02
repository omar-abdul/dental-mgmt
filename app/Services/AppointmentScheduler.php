<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\FeeItem;
use App\Models\WorkingHour;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AppointmentScheduler
{
    public function calculateEndsAt(Carbon $startsAt, ?FeeItem $feeItem, ?int $durationMinutes): Carbon
    {
        $minutes = $durationMinutes ?? $feeItem?->default_duration_minutes ?? 30;

        return $startsAt->copy()->addMinutes($minutes);
    }

    public function validateWorkingHours(Carbon $startsAt, Carbon $endsAt): void
    {
        $weekday = $startsAt->dayOfWeek;

        $workingHour = WorkingHour::query()
            ->where('weekday', $weekday)
            ->first();

        if ($workingHour === null || $workingHour->opens_at === null || $workingHour->closes_at === null) {
            throw ValidationException::withMessages([
                'starts_at' => __('The clinic is closed on this day.'),
            ]);
        }

        $opensAt = $startsAt->copy()->setTimeFromTimeString($workingHour->opens_at);
        $closesAt = $startsAt->copy()->setTimeFromTimeString($workingHour->closes_at);

        if ($startsAt->lt($opensAt) || $endsAt->gt($closesAt)) {
            throw ValidationException::withMessages([
                'starts_at' => __('The appointment is outside working hours.'),
            ]);
        }
    }

    public function assertNoOverlap(
        int $dentistId,
        int $chairId,
        Carbon $startsAt,
        Carbon $endsAt,
        ?int $excludeAppointmentId = null,
    ): void {
        $query = Appointment::query()
            ->whereNotIn('status', [
                AppointmentStatus::Cancelled,
                AppointmentStatus::NoShow,
            ])
            ->where(function ($query) use ($dentistId, $chairId): void {
                $query->where('dentist_id', $dentistId)
                    ->orWhere('chair_id', $chairId);
            })
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);

        if ($excludeAppointmentId !== null) {
            $query->whereKeyNot($excludeAppointmentId);
        }

        if ($query->lockForUpdate()->exists()) {
            throw ValidationException::withMessages([
                'starts_at' => __('This time slot conflicts with another appointment.'),
            ]);
        }
    }
}
