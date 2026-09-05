<?php

namespace App\Http\Controllers;

use App\Enums\ClinicRole;
use App\Models\User;
use App\Services\ReportDateRange;
use App\Services\ReportsQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    public function index(Request $request, ReportsQuery $reports): Response
    {
        $user = $this->authorizeReports($request);
        $range = ReportDateRange::fromRequest($request);
        $canViewFinance = $user->role->canViewReportsFinance();
        $dentistId = $this->scopedDentistId($user);

        return Inertia::render('reports/Index', [
            'filters' => $range->toFilterProps(),
            'canViewFinance' => $canViewFinance,
            'summary' => $reports->hubSummary($range, $canViewFinance, $dentistId),
            'reports' => $this->reportCards($canViewFinance),
        ]);
    }

    public function dailyAppointments(Request $request, ReportsQuery $reports): Response
    {
        $user = $this->authorizeReports($request);
        $range = ReportDateRange::fromRequest($request);
        $data = $reports->dailyAppointments($range, $this->scopedDentistId($user));

        return Inertia::render('reports/DailyAppointments', [
            ...$this->sharedProps($user, $range),
            'report' => $data,
        ]);
    }

    public function patientRegistration(Request $request, ReportsQuery $reports): Response
    {
        $user = $this->authorizeReports($request);
        $range = ReportDateRange::fromRequest($request);

        return Inertia::render('reports/PatientRegistration', [
            ...$this->sharedProps($user, $range),
            'report' => $reports->patientRegistrations($range),
        ]);
    }

    public function outstandingBalances(Request $request, ReportsQuery $reports): Response
    {
        $user = $this->authorizeFinanceReports($request);
        $range = ReportDateRange::fromRequest($request);
        $data = $reports->outstandingBalances($range);
        $data['total_balance_formatted'] = $this->formatCents($data['total_balance_cents']);

        return Inertia::render('reports/OutstandingBalances', [
            ...$this->sharedProps($user, $range),
            'report' => $data,
        ]);
    }

    public function payments(Request $request, ReportsQuery $reports): Response
    {
        $user = $this->authorizeFinanceReports($request);
        $range = ReportDateRange::fromRequest($request);

        return Inertia::render('reports/Payments', [
            ...$this->sharedProps($user, $range),
            'report' => $reports->payments($range),
        ]);
    }

    public function inventoryStock(Request $request, ReportsQuery $reports): Response
    {
        $user = $this->authorizeReports($request);
        $range = ReportDateRange::fromRequest($request);

        return Inertia::render('reports/InventoryStock', [
            ...$this->sharedProps($user, $range),
            'report' => $reports->inventoryStock(),
        ]);
    }

    public function lowStock(Request $request, ReportsQuery $reports): Response
    {
        $user = $this->authorizeReports($request);
        $range = ReportDateRange::fromRequest($request);

        return Inertia::render('reports/LowStock', [
            ...$this->sharedProps($user, $range),
            'report' => $reports->lowStock(),
        ]);
    }

    public function treatmentStatistics(Request $request, ReportsQuery $reports): Response
    {
        $user = $this->authorizeReports($request);
        $range = ReportDateRange::fromRequest($request);
        $data = $reports->treatmentStatistics($range, $this->scopedDentistId($user));

        return Inertia::render('reports/TreatmentStatistics', [
            ...$this->sharedProps($user, $range),
            'report' => $data,
        ]);
    }

    private function authorizeReports(Request $request): User
    {
        $user = $request->user();

        abort_unless($user && $user->role->canViewModule('reports'), 403);

        return $user;
    }

    private function authorizeFinanceReports(Request $request): User
    {
        $user = $this->authorizeReports($request);

        abort_unless($user->role->canViewReportsFinance(), 403);

        return $user;
    }

    private function scopedDentistId(User $user): ?int
    {
        if ($user->role !== ClinicRole::Dentist) {
            return null;
        }

        return $user->dentist?->id;
    }

    /**
     * @return array{filters: array{from: string, to: string}, canViewFinance: bool}
     */
    private function sharedProps(User $user, ReportDateRange $range): array
    {
        return [
            'filters' => $range->toFilterProps(),
            'canViewFinance' => $user->role->canViewReportsFinance(),
        ];
    }

    /**
     * @return list<array{key: string, title: string, description: string, finance: bool}>
     */
    private function reportCards(bool $canViewFinance): array
    {
        $cards = [
            [
                'key' => 'daily-appointments',
                'title' => 'Daily appointments',
                'description' => 'Appointment volume and status breakdown for the selected range.',
                'finance' => false,
            ],
            [
                'key' => 'patient-registration',
                'title' => 'Patient registration',
                'description' => 'New patients registered during the selected range.',
                'finance' => false,
            ],
            [
                'key' => 'outstanding-balances',
                'title' => 'Outstanding balances',
                'description' => 'Open invoice balances across the clinic.',
                'finance' => true,
            ],
            [
                'key' => 'payments',
                'title' => 'Payments',
                'description' => 'Completed payments with method breakdown.',
                'finance' => true,
            ],
            [
                'key' => 'inventory-stock',
                'title' => 'Inventory stock',
                'description' => 'Current stock levels and valuation snapshot.',
                'finance' => false,
            ],
            [
                'key' => 'low-stock',
                'title' => 'Low stock',
                'description' => 'Items at or below reorder level.',
                'finance' => false,
            ],
            [
                'key' => 'treatment-statistics',
                'title' => 'Treatment statistics',
                'description' => 'Treatment counts and procedure activity.',
                'finance' => false,
            ],
        ];

        if (! $canViewFinance) {
            return array_values(array_filter(
                $cards,
                fn (array $card): bool => ! $card['finance'],
            ));
        }

        return $cards;
    }

    private function formatCents(int $cents): string
    {
        return '$'.number_format($cents / 100, 2);
    }
}
