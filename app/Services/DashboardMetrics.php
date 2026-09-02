<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PatientStatus;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardMetrics
{
    /**
     * @var list<AppointmentStatus>
     */
    private const VACATED_STATUSES = [
        AppointmentStatus::Cancelled,
        AppointmentStatus::NoShow,
        AppointmentStatus::Rescheduled,
    ];

    /**
     * @return array{
     *     kpis: array{
     *         todays_appointments: int|null,
     *         active_patients: int|null,
     *         unpaid_invoices: int|null,
     *         low_stock_items: int|null,
     *     },
     *     weekly_visits: list<array{key: string, label: string, count: int}>|null,
     *     recent_activity: list<array{id: int, action: string, description: string|null, user_name: string, created_at: string|null}>,
     *     upcoming: list<array{id: int, number: string, starts_at: string, time_label: string, patient_name: string, dentist_name: string, status: string, status_label: string}>|null,
     * }
     */
    public function forUser(User $user): array
    {
        $role = $user->role;
        $now = Carbon::now();

        return [
            'kpis' => [
                'todays_appointments' => $role->canViewModule('appointments')
                    ? $this->todaysAppointmentsCount($now)
                    : null,
                'active_patients' => $role->canViewModule('patients')
                    ? $this->activePatientsCount()
                    : null,
                'unpaid_invoices' => $role->canViewModule('billing')
                    ? $this->unpaidInvoicesCount()
                    : null,
                'low_stock_items' => $role->canViewModule('inventory')
                    ? $this->lowStockItemsCount()
                    : null,
            ],
            'weekly_visits' => $role->canViewModule('appointments')
                ? $this->weeklyVisits($now)
                : null,
            'recent_activity' => $this->recentActivity(),
            'upcoming' => $role->canViewModule('appointments')
                ? $this->upcomingToday($now)
                : null,
        ];
    }

    private function todaysAppointmentsCount(Carbon $now): int
    {
        [$dayStart, $dayEnd] = $this->dayBounds($now);

        return Appointment::query()
            ->where('starts_at', '>=', $dayStart)
            ->where('starts_at', '<', $dayEnd)
            ->whereNotIn('status', self::VACATED_STATUSES)
            ->count();
    }

    private function activePatientsCount(): int
    {
        return Patient::query()
            ->where('status', PatientStatus::Active)
            ->count();
    }

    private function unpaidInvoicesCount(): int
    {
        return Invoice::query()
            ->whereIn('status', [
                InvoiceStatus::Issued,
                InvoiceStatus::PartiallyPaid,
                InvoiceStatus::Overdue,
            ])
            ->count();
    }

    private function lowStockItemsCount(): int
    {
        return InventoryItem::query()
            ->where('quantity', '>', 0)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->count();
    }

    /**
     * @return list<array{key: string, label: string, count: int}>
     */
    private function weeklyVisits(Carbon $now): array
    {
        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();

        $days = [
            ['key' => 'mon', 'label' => 'Mon'],
            ['key' => 'tue', 'label' => 'Tue'],
            ['key' => 'wed', 'label' => 'Wed'],
            ['key' => 'thu', 'label' => 'Thu'],
            ['key' => 'fri', 'label' => 'Fri'],
            ['key' => 'sat', 'label' => 'Sat'],
            ['key' => 'sun', 'label' => 'Sun'],
        ];

        $visits = [];

        foreach ($days as $index => $day) {
            $dayStart = $weekStart->copy()->addDays($index);
            $dayEnd = $dayStart->copy()->addDay();

            $visits[] = [
                'key' => $day['key'],
                'label' => $day['label'],
                'count' => Appointment::query()
                    ->where('starts_at', '>=', $dayStart)
                    ->where('starts_at', '<', $dayEnd)
                    ->whereNotIn('status', self::VACATED_STATUSES)
                    ->count(),
            ];
        }

        return $visits;
    }

    /**
     * @return list<array{id: int, action: string, description: string|null, user_name: string, created_at: string|null}>
     */
    private function recentActivity(): array
    {
        return ActivityLog::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (ActivityLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user_name' => $log->user?->name ?? 'System',
                'created_at' => $log->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, number: string, starts_at: string, time_label: string, patient_name: string, dentist_name: string, status: string, status_label: string}>
     */
    private function upcomingToday(Carbon $now): array
    {
        [$dayStart, $dayEnd] = $this->dayBounds($now);

        return Appointment::query()
            ->with(['patient', 'dentist'])
            ->where('starts_at', '>=', $dayStart)
            ->where('starts_at', '<', $dayEnd)
            ->where('starts_at', '>=', $now)
            ->whereNotIn('status', self::VACATED_STATUSES)
            ->orderBy('starts_at')
            ->limit(12)
            ->get()
            ->map(fn (Appointment $appointment): array => [
                'id' => $appointment->id,
                'number' => $appointment->number,
                'starts_at' => $appointment->starts_at->toIso8601String(),
                'time_label' => $appointment->starts_at->format('H:i'),
                'patient_name' => "{$appointment->patient->first_name} {$appointment->patient->last_name}",
                'dentist_name' => $appointment->dentist->display_name,
                'status' => $appointment->status->value,
                'status_label' => ucfirst(str_replace('_', ' ', $appointment->status->value)),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function dayBounds(Carbon $moment): array
    {
        $dayStart = $moment->copy()->startOfDay();
        $dayEnd = $dayStart->copy()->addDay();

        return [$dayStart, $dayEnd];
    }
}
