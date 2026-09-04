<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\InventoryCategory;
use App\Enums\InvoiceStatus;
use App\Models\Appointment;
use App\Models\Chair;
use App\Models\Dentist;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Patient;
use Database\Seeders\GoldenSmile\GoldenSmileFixture;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class GoldenSmileGenerateSeeder extends Seeder
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
     * @var array<string, int>
     */
    private const WEEKDAY_OFFSETS = [
        'mon' => 0,
        'tue' => 1,
        'wed' => 2,
        'thu' => 3,
        'fri' => 4,
        'sat' => 5,
        'sun' => 6,
    ];

    public function run(): void
    {
        $this->seedExtraPatients();
        $this->seedExtraPendingInvoices();
        $this->seedWeeklyVisitAppointments();
        $this->seedExtraTodaysAppointments();
        $this->seedExtraInventory();
    }

    private function seedExtraPatients(): void
    {
        $count = GoldenSmileFixture::extraActivePatientsCount();

        if ($count <= 0) {
            return;
        }

        Patient::factory()->count($count)->create();
    }

    private function seedExtraPendingInvoices(): void
    {
        $count = GoldenSmileFixture::extraPendingInvoicesCount();

        if ($count <= 0) {
            return;
        }

        $patientIds = Patient::query()->pluck('id')->all();

        for ($index = 0; $index < $count; $index++) {
            Invoice::factory()->create([
                'patient_id' => $patientIds[$index % count($patientIds)],
                'status' => InvoiceStatus::Issued,
            ]);
        }
    }

    private function seedWeeklyVisitAppointments(): void
    {
        $now = Carbon::now();
        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $todayKey = $this->todayKey($now);

        foreach (GoldenSmileFixture::weeklyVisitTargets() as $dayKey => $target) {
            if ($dayKey === $todayKey) {
                continue;
            }

            $this->createAppointmentsForDay(
                dayStart: $weekStart->copy()->addDays(self::WEEKDAY_OFFSETS[$dayKey]),
                count: max(0, $target - $this->appointmentCountForDay(
                    $weekStart->copy()->addDays(self::WEEKDAY_OFFSETS[$dayKey]),
                )),
            );
        }
    }

    private function seedExtraTodaysAppointments(): void
    {
        $count = GoldenSmileFixture::extraTodaysAppointmentsCount();

        if ($count <= 0) {
            return;
        }

        $this->createAppointmentsForDay(
            dayStart: Carbon::today(),
            count: $count,
            hourOffset: 14,
        );
    }

    private function createAppointmentsForDay(Carbon $dayStart, int $count, int $hourOffset = 8): void
    {
        if ($count <= 0) {
            return;
        }

        $dentistIds = Dentist::query()->pluck('id')->all();
        $chairIds = Chair::query()->pluck('id')->all();
        $patientIds = Patient::query()->pluck('id')->all();

        if ($dentistIds === [] || $chairIds === [] || $patientIds === []) {
            return;
        }

        for ($index = 0; $index < $count; $index++) {
            $startsAt = $dayStart->copy()->setTime(
                min(17, $hourOffset + intdiv($index, 2)),
                ($index % 2) * 30,
            );

            Appointment::factory()->create([
                'patient_id' => $patientIds[$index % count($patientIds)],
                'dentist_id' => $dentistIds[$index % count($dentistIds)],
                'chair_id' => $chairIds[$index % count($chairIds)],
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addMinutes(30),
                'status' => AppointmentStatus::Confirmed,
            ]);
        }
    }

    private function appointmentCountForDay(Carbon $dayStart): int
    {
        $dayEnd = $dayStart->copy()->addDay();

        return Appointment::query()
            ->where('starts_at', '>=', $dayStart)
            ->where('starts_at', '<', $dayEnd)
            ->whereNotIn('status', self::VACATED_STATUSES)
            ->count();
    }

    private function todayKey(Carbon $now): string
    {
        return match ($now->dayOfWeekIso) {
            1 => 'mon',
            2 => 'tue',
            3 => 'wed',
            4 => 'thu',
            5 => 'fri',
            6 => 'sat',
            7 => 'sun',
            default => 'mon',
        };
    }

    private function seedExtraInventory(): void
    {
        $extraItems = GoldenSmileFixture::extraInventoryItemCount();
        $extraLowStock = GoldenSmileFixture::extraLowStockItemCount();
        $remainingValueCents = GoldenSmileFixture::extraStockValueCents();

        if ($extraItems <= 0) {
            return;
        }

        for ($index = 0; $index < $extraLowStock; $index++) {
            $lowUnitCost = 500;
            InventoryItem::query()->create([
                'name' => sprintf('Generated Low Stock Item %d', $index + 1),
                'category' => InventoryCategory::Consumables,
                'quantity' => 2,
                'unit' => 'Pack',
                'reorder_level' => 5,
                'unit_cost_cents' => $lowUnitCost,
            ]);
            $remainingValueCents -= 2 * $lowUnitCost;
            $extraItems--;
        }

        if ($extraItems <= 0) {
            return;
        }

        $inStockQuantity = 10;
        $inStockReorder = 2;

        for ($index = 0; $index < $extraItems; $index++) {
            $itemsRemaining = $extraItems - $index;
            $lineValueCents = $index === $extraItems - 1
                ? $remainingValueCents
                : intdiv($remainingValueCents, $itemsRemaining);
            $unitCostCents = max(100, intdiv($lineValueCents, $inStockQuantity));
            $lineValueCents = $unitCostCents * $inStockQuantity;
            $remainingValueCents -= $lineValueCents;

            InventoryItem::query()->create([
                'name' => sprintf('Generated Inventory Item %03d', $index + 1),
                'category' => InventoryCategory::Consumables,
                'quantity' => $inStockQuantity,
                'unit' => 'Unit',
                'reorder_level' => $inStockReorder,
                'unit_cost_cents' => $unitCostCents,
            ]);
        }
    }
}
