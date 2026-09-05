<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Treatment;
use App\Models\TreatmentProcedure;
use Illuminate\Database\Eloquent\Builder;

class ReportsQuery
{
    /**
     * @var list<AppointmentStatus>
     */
    private const VACATED_APPOINTMENT_STATUSES = [
        AppointmentStatus::Cancelled,
        AppointmentStatus::NoShow,
        AppointmentStatus::Rescheduled,
    ];

    /**
     * @var list<InvoiceStatus>
     */
    private const OUTSTANDING_INVOICE_STATUSES = [
        InvoiceStatus::Issued,
        InvoiceStatus::PartiallyPaid,
        InvoiceStatus::Overdue,
    ];

    /**
     * @return array{
     *     total: int,
     *     by_status: list<array{status: string, status_label: string, count: int}>,
     *     rows: list<array{id: int, number: string, starts_at: string, patient_name: string, dentist_name: string, status: string, status_label: string}>
     * }
     */
    public function dailyAppointments(ReportDateRange $range, ?int $dentistId = null): array
    {
        $query = Appointment::query()
            ->with(['patient', 'dentist'])
            ->where('starts_at', '>=', $range->from)
            ->where('starts_at', '<=', $range->to)
            ->whereNotIn('status', self::VACATED_APPOINTMENT_STATUSES)
            ->when($dentistId !== null, fn (Builder $query) => $query->where('dentist_id', $dentistId));

        $appointments = (clone $query)
            ->orderBy('starts_at')
            ->limit(100)
            ->get();

        $statusCounts = (clone $query)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->orderBy('status')
            ->pluck('aggregate', 'status');

        return [
            'total' => (int) (clone $query)->count(),
            'by_status' => $statusCounts
                ->map(fn (int $count, string $status): array => [
                    'status' => $status,
                    'status_label' => ucfirst(str_replace('_', ' ', $status)),
                    'count' => $count,
                ])
                ->values()
                ->all(),
            'rows' => $appointments
                ->map(fn (Appointment $appointment): array => [
                    'id' => $appointment->id,
                    'number' => $appointment->number,
                    'starts_at' => $appointment->starts_at->toIso8601String(),
                    'patient_name' => "{$appointment->patient->first_name} {$appointment->patient->last_name}",
                    'dentist_name' => $appointment->dentist->display_name,
                    'status' => $appointment->status->value,
                    'status_label' => ucfirst(str_replace('_', ' ', $appointment->status->value)),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{
     *     total: int,
     *     rows: list<array{id: int, patient_number: string, name: string, registered_at: string}>
     * }
     */
    public function patientRegistrations(ReportDateRange $range): array
    {
        $patients = Patient::query()
            ->where('created_at', '>=', $range->from)
            ->where('created_at', '<=', $range->to)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return [
            'total' => Patient::query()
                ->where('created_at', '>=', $range->from)
                ->where('created_at', '<=', $range->to)
                ->count(),
            'rows' => $patients
                ->map(fn (Patient $patient): array => [
                    'id' => $patient->id,
                    'patient_number' => $patient->patient_number,
                    'name' => "{$patient->first_name} {$patient->last_name}",
                    'registered_at' => $patient->created_at?->toIso8601String() ?? '',
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{
     *     invoice_count: int,
     *     total_balance_cents: int,
     *     rows: list<array{id: int, invoice_number: string, patient_name: string, issued_at: string, balance_cents: int, balance_formatted: string, status: string, status_label: string}>
     * }
     */
    public function outstandingBalances(ReportDateRange $range): array
    {
        $baseQuery = Invoice::query()
            ->whereIn('status', self::OUTSTANDING_INVOICE_STATUSES)
            ->where('balance_cents', '>', 0)
            ->where('issued_at', '>=', $range->from)
            ->where('issued_at', '<=', $range->to);

        $invoices = (clone $baseQuery)
            ->with('patient')
            ->orderByDesc('issued_at')
            ->limit(100)
            ->get();

        $totalBalanceCents = (int) (clone $baseQuery)->sum('balance_cents');

        return [
            'invoice_count' => (int) (clone $baseQuery)->count(),
            'total_balance_cents' => $totalBalanceCents,
            'rows' => $invoices
                ->map(fn (Invoice $invoice): array => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'patient_name' => "{$invoice->patient->first_name} {$invoice->patient->last_name}",
                    'issued_at' => $invoice->issued_at->toIso8601String(),
                    'balance_cents' => $invoice->balance_cents,
                    'balance_formatted' => $this->formatCents($invoice->balance_cents),
                    'status' => $invoice->status->value,
                    'status_label' => ucfirst(str_replace('_', ' ', $invoice->status->value)),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{
     *     payment_count: int,
     *     total_cents: int,
     *     total_formatted: string,
     *     by_method: list<array{method: string, method_label: string, count: int, total_cents: int, total_formatted: string}>,
     *     rows: list<array{id: int, payment_number: string, paid_at: string, patient_name: string, method: string, method_label: string, amount_cents: int, amount_formatted: string}>
     * }
     */
    public function payments(ReportDateRange $range): array
    {
        $baseQuery = Payment::query()
            ->where('status', PaymentStatus::Completed)
            ->where('paid_at', '>=', $range->from)
            ->where('paid_at', '<=', $range->to);

        $totalCents = (int) (clone $baseQuery)->sum('amount_cents');

        $methodRows = (clone $baseQuery)
            ->toBase()
            ->selectRaw('method, COUNT(*) as payment_count, SUM(amount_cents) as total_cents')
            ->groupBy('method')
            ->orderBy('method')
            ->get();

        $payments = (clone $baseQuery)
            ->with('patient')
            ->orderByDesc('paid_at')
            ->limit(100)
            ->get();

        return [
            'payment_count' => (int) (clone $baseQuery)->count(),
            'total_cents' => $totalCents,
            'total_formatted' => $this->formatCents($totalCents),
            'by_method' => $methodRows
                ->map(function (object $row): array {
                    $method = (string) $row->method;
                    $totalCents = (int) $row->total_cents;

                    return [
                        'method' => $method,
                        'method_label' => ucfirst(str_replace('_', ' ', $method)),
                        'count' => (int) $row->payment_count,
                        'total_cents' => $totalCents,
                        'total_formatted' => $this->formatCents($totalCents),
                    ];
                })
                ->values()
                ->all(),
            'rows' => $payments
                ->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'paid_at' => $payment->paid_at?->toIso8601String() ?? '',
                    'patient_name' => "{$payment->patient->first_name} {$payment->patient->last_name}",
                    'method' => $payment->method->value,
                    'method_label' => ucfirst(str_replace('_', ' ', $payment->method->value)),
                    'amount_cents' => $payment->amount_cents,
                    'amount_formatted' => $this->formatCents($payment->amount_cents),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{
     *     total_items: int,
     *     stock_value_cents: int,
     *     stock_value_formatted: string,
     *     rows: list<array{id: int, name: string, category: string, category_label: string, quantity: int, unit: string, reorder_level: int, stock_status: string, stock_status_label: string}>
     * }
     */
    public function inventoryStock(): array
    {
        $items = InventoryItem::query()
            ->orderBy('name')
            ->get();

        $stockValueCents = $items->sum(
            fn (InventoryItem $item): int => $item->quantity * $item->unit_cost_cents,
        );

        return [
            'total_items' => $items->count(),
            'stock_value_cents' => $stockValueCents,
            'stock_value_formatted' => $this->formatCents($stockValueCents),
            'rows' => $items
                ->map(fn (InventoryItem $item): array => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => $item->category->value,
                    'category_label' => ucfirst(str_replace('_', ' ', $item->category->value)),
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'reorder_level' => $item->reorder_level,
                    'stock_status' => $item->stockStatus()->value,
                    'stock_status_label' => $item->stockStatus()->label(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{
     *     total: int,
     *     rows: list<array{id: int, name: string, category: string, category_label: string, quantity: int, unit: string, reorder_level: int}>
     * }
     */
    public function lowStock(): array
    {
        $items = InventoryItem::query()
            ->where('quantity', '>', 0)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->orderBy('quantity')
            ->orderBy('name')
            ->get();

        return [
            'total' => $items->count(),
            'rows' => $items
                ->map(fn (InventoryItem $item): array => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => $item->category->value,
                    'category_label' => ucfirst(str_replace('_', ' ', $item->category->value)),
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'reorder_level' => $item->reorder_level,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{
     *     total: int,
     *     by_status: list<array{status: string, status_label: string, count: int}>,
     *     procedure_count: int,
     *     procedure_fees_cents: int,
     *     procedure_fees_formatted: string,
     *     rows: list<array{id: int, patient_name: string, dentist_name: string, diagnosed_at: string, diagnosis: string, status: string, status_label: string, procedure_count: int}>
     * }
     */
    public function treatmentStatistics(ReportDateRange $range, ?int $dentistId = null): array
    {
        $query = Treatment::query()
            ->with(['patient', 'dentist', 'procedures'])
            ->where('diagnosed_at', '>=', $range->from)
            ->where('diagnosed_at', '<=', $range->to)
            ->when($dentistId !== null, fn (Builder $query) => $query->where('dentist_id', $dentistId));

        $treatments = (clone $query)
            ->orderByDesc('diagnosed_at')
            ->limit(100)
            ->get();

        $statusCounts = (clone $query)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->orderBy('status')
            ->pluck('aggregate', 'status');

        $treatmentIds = (clone $query)->pluck('id');

        $procedureStats = TreatmentProcedure::query()
            ->whereIn('treatment_id', $treatmentIds)
            ->selectRaw('COUNT(*) as procedure_count, COALESCE(SUM(fee_cents * quantity), 0) as total_fees')
            ->first();

        return [
            'total' => (int) (clone $query)->count(),
            'by_status' => $statusCounts
                ->map(fn (int $count, string $status): array => [
                    'status' => $status,
                    'status_label' => ucfirst(str_replace('_', ' ', $status)),
                    'count' => $count,
                ])
                ->values()
                ->all(),
            'procedure_count' => (int) ($procedureStats->procedure_count ?? 0),
            'procedure_fees_cents' => (int) ($procedureStats->total_fees ?? 0),
            'procedure_fees_formatted' => $this->formatCents((int) ($procedureStats->total_fees ?? 0)),
            'rows' => $treatments
                ->map(fn (Treatment $treatment): array => [
                    'id' => $treatment->id,
                    'patient_name' => "{$treatment->patient->first_name} {$treatment->patient->last_name}",
                    'dentist_name' => $treatment->dentist->display_name,
                    'diagnosed_at' => $treatment->diagnosed_at->toIso8601String(),
                    'diagnosis' => $treatment->diagnosis,
                    'status' => $treatment->status->value,
                    'status_label' => ucfirst(str_replace('_', ' ', $treatment->status->value)),
                    'procedure_count' => $treatment->procedures->count(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{
     *     appointments: int,
     *     registrations: int,
     *     payments_cents: int|null,
     *     payments_formatted: string|null,
     *     outstanding_cents: int|null,
     *     outstanding_formatted: string|null,
     * }
     */
    public function hubSummary(ReportDateRange $range, bool $canViewFinance, ?int $dentistId = null): array
    {
        $appointments = $this->dailyAppointments($range, $dentistId)['total'];
        $registrations = $this->patientRegistrations($range)['total'];

        $summary = [
            'appointments' => $appointments,
            'registrations' => $registrations,
            'payments_cents' => null,
            'payments_formatted' => null,
            'outstanding_cents' => null,
            'outstanding_formatted' => null,
        ];

        if ($canViewFinance) {
            $payments = $this->payments($range);
            $outstanding = $this->outstandingBalances($range);

            $summary['payments_cents'] = $payments['total_cents'];
            $summary['payments_formatted'] = $payments['total_formatted'];
            $summary['outstanding_cents'] = $outstanding['total_balance_cents'];
            $summary['outstanding_formatted'] = $this->formatCents($outstanding['total_balance_cents']);
        }

        return $summary;
    }

    private function formatCents(int $cents): string
    {
        return '$'.number_format($cents / 100, 2);
    }
}
