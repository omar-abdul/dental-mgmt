<?php

namespace Database\Seeders\GoldenSmile;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use RuntimeException;

class GoldenSmileFixture
{
    /**
     * @var array<string, mixed>|null
     */
    private static ?array $data = null;

    /**
     * @return array<string, mixed>
     */
    public static function data(): array
    {
        if (self::$data === null) {
            $path = database_path('data/golden-smile.example.json');
            self::$data = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        }

        return self::$data;
    }

    public static function demoPassword(): string
    {
        return (string) self::data()['auth']['demo_password'];
    }

    /**
     * @return array<string, int>
     */
    public static function kpis(): array
    {
        return self::data()['kpis'];
    }

    /**
     * @return array<string, int>
     */
    public static function weeklyVisitTargets(): array
    {
        return self::data()['kpis']['weekly_visits'];
    }

    public static function namedPatientCount(): int
    {
        return count(self::data()['patients']);
    }

    public static function namedActivePatientCount(): int
    {
        return collect(self::data()['patients'])
            ->where('status', 'active')
            ->count();
    }

    public static function namedTodayAppointmentCount(): int
    {
        return count(self::data()['appointments']);
    }

    public static function namedPendingInvoiceCount(): int
    {
        return collect(self::data()['invoices'])
            ->filter(fn (array $invoice): bool => in_array($invoice['status'], ['issued', 'partially_paid', 'overdue'], true))
            ->count();
    }

    public static function namedInventoryItemCount(): int
    {
        return count(self::data()['inventory_items']);
    }

    public static function namedLowStockItemCount(): int
    {
        return collect(self::data()['inventory_items'])
            ->filter(fn (array $item): bool => $item['quantity'] > 0 && $item['quantity'] <= $item['reorder_level'])
            ->count();
    }

    public static function namedOutOfStockItemCount(): int
    {
        return collect(self::data()['inventory_items'])
            ->where('quantity', 0)
            ->count();
    }

    public static function namedStockValueCents(): int
    {
        return (int) collect(self::data()['inventory_items'])
            ->sum(fn (array $item): int => $item['quantity'] * $item['unit_cost_cents']);
    }

    public static function extraActivePatientsCount(): int
    {
        return self::kpis()['active_patients'] - self::namedActivePatientCount();
    }

    public static function extraTodaysAppointmentsCount(): int
    {
        return self::kpis()['todays_appointments'] - self::namedTodayAppointmentCount();
    }

    public static function extraPendingInvoicesCount(): int
    {
        return self::kpis()['pending_invoices'] - self::namedPendingInvoiceCount();
    }

    public static function extraInventoryItemCount(): int
    {
        return self::kpis()['inventory_item_count'] - self::namedInventoryItemCount();
    }

    public static function extraLowStockItemCount(): int
    {
        return self::kpis()['low_stock_items'] - self::namedLowStockItemCount();
    }

    public static function extraStockValueCents(): int
    {
        return self::kpis()['stock_value_cents'] - self::namedStockValueCents();
    }

    public static function parseWhen(string $when): Carbon
    {
        if (preg_match('/^today\s+(\d{1,2}):(\d{2})$/', $when, $matches) === 1) {
            return Carbon::today()->setTime((int) $matches[1], (int) $matches[2]);
        }

        throw new RuntimeException("Unsupported appointment when expression [{$when}].");
    }

    public static function parseRelative(string $relative): Carbon
    {
        if (preg_match('/^(\d+)\s+(minute|hour|day)s?\s+ago$/', $relative, $matches) !== 1) {
            throw new RuntimeException("Unsupported relative time expression [{$relative}].");
        }

        $amount = (int) $matches[1];

        return match ($matches[2]) {
            'minute' => Carbon::now()->subMinutes($amount),
            'hour' => Carbon::now()->subHours($amount),
            'day' => Carbon::now()->subDays($amount),
            default => throw new RuntimeException("Unsupported relative time unit [{$matches[2]}]."),
        };
    }

    /**
     * @return list<string>
     */
    public static function feeDcmsIds(): array
    {
        return collect(self::data()['fee_items'])
            ->pluck('dcms_id')
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function feeCodesByKey(): array
    {
        return collect(self::data()['fee_items'])
            ->mapWithKeys(fn (array $item): array => [$item['key'] => $item['code']])
            ->all();
    }

    public static function reset(): void
    {
        self::$data = null;
    }
}
