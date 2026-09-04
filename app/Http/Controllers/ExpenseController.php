<?php

namespace App\Http\Controllers;

use App\Enums\ReconciliationStatus;
use App\Http\Requests\StoreDailyCashClosingRequest;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\StoreMobileMoneyReconciliationRequest;
use App\Models\DailyCashClosing;
use App\Models\Expense;
use App\Models\MobileMoneyReconciliation;
use App\Services\DailyCashClosingService;
use App\Services\MobileMoneyReconciliationService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request, DailyCashClosingService $cashClosingService): Response
    {
        Gate::authorize('viewAny', Expense::class);

        $today = Carbon::now('Africa/Mogadishu')->startOfDay();
        $todayClosing = DailyCashClosing::query()
            ->whereDate('closing_date', $today)
            ->first();

        $expenses = Expense::query()
            ->with('recorder')
            ->orderByDesc('expense_date')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->through(fn (Expense $expense) => $this->expenseListItem($expense));

        $recentClosings = DailyCashClosing::query()
            ->with('closer')
            ->orderByDesc('closing_date')
            ->limit(5)
            ->get()
            ->map(fn (DailyCashClosing $closing) => $this->closingListItem($closing))
            ->values();

        $recentReconciliations = MobileMoneyReconciliation::query()
            ->with('reconciler')
            ->orderByDesc('reconciliation_date')
            ->limit(5)
            ->get()
            ->map(fn (MobileMoneyReconciliation $reconciliation) => $this->reconciliationListItem($reconciliation))
            ->values();

        return Inertia::render('expenses/Index', [
            'expenses' => $expenses,
            'canCreate' => $request->user()?->can('create', Expense::class) ?? false,
            'canCloseCash' => $request->user()?->can('create', DailyCashClosing::class) ?? false,
            'canReconcileMobileMoney' => $request->user()?->can('create', MobileMoneyReconciliation::class) ?? false,
            'todayClosingDate' => $today->toDateString(),
            'todaySystemCashTotalCents' => $cashClosingService->systemCashTotalCentsForDate($today),
            'todaySystemCashTotalFormatted' => $this->formatCents($cashClosingService->systemCashTotalCentsForDate($today)),
            'todayClosing' => $todayClosing ? $this->closingListItem($todayClosing) : null,
            'recentClosings' => $recentClosings,
            'recentReconciliations' => $recentReconciliations,
            'mobileMoneyProviders' => $this->mobileMoneyProviderOptions(),
            'expenseCategories' => $this->expenseCategoryOptions(),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user !== null, 403);

        $data = $request->expenseData();

        Expense::query()->create([
            ...$data,
            'recorded_by' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense recorded.')]);

        return to_route('expenses.index');
    }

    public function storeDailyClosing(
        StoreDailyCashClosingRequest $request,
        DailyCashClosingService $cashClosingService,
    ): RedirectResponse {
        $user = $request->user();

        abort_unless($user !== null, 403);

        $closingDate = Carbon::parse($request->closingDate(), 'Africa/Mogadishu')->startOfDay();
        $countedCashCents = $request->countedCashCents();

        try {
            DB::transaction(function () use ($cashClosingService, $closingDate, $countedCashCents, $user, $request): void {
                $systemTotalCents = $cashClosingService->systemCashTotalCentsForDate($closingDate);

                $closing = DailyCashClosing::query()->make([
                    'closing_date' => $closingDate->toDateString(),
                    'counted_cash_cents' => $countedCashCents,
                    'closed_by' => $user->id,
                    'closed_at' => now(),
                    'notes' => $request->notes(),
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
                $closing->system_cash_total_cents = $systemTotalCents;
                $closing->difference_cents = $countedCashCents - $systemTotalCents;
                $closing->save();
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'closing_date' => __('A cash closing already exists for this date.'),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Daily cash closing recorded.')]);

        return to_route('expenses.index');
    }

    public function storeMobileMoneyReconciliation(
        StoreMobileMoneyReconciliationRequest $request,
        MobileMoneyReconciliationService $reconciliationService,
    ): RedirectResponse {
        $user = $request->user();

        abort_unless($user !== null, 403);

        $reconciliationDate = Carbon::parse($request->reconciliationDate(), 'Africa/Mogadishu')->startOfDay();
        $provider = $request->provider();
        $providerTotalCents = $request->providerTotalCents();

        DB::transaction(function () use (
            $reconciliationService,
            $reconciliationDate,
            $provider,
            $providerTotalCents,
            $user,
            $request,
        ): void {
            $systemTotals = $reconciliationService->systemTotalsForDateAndProvider($reconciliationDate, $provider);
            $differenceCents = $providerTotalCents - $systemTotals['system_total_cents'];

            $reconciliation = MobileMoneyReconciliation::query()->make([
                'reconciliation_date' => $reconciliationDate->toDateString(),
                'provider' => $provider,
                'provider_total_cents' => $providerTotalCents,
                'reconciled_by' => $user->id,
                'reconciled_at' => now(),
                'status' => $differenceCents === 0
                    ? ReconciliationStatus::Reconciled
                    : ReconciliationStatus::Discrepancy,
                'notes' => $request->notes(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
            $reconciliation->transaction_count = $systemTotals['transaction_count'];
            $reconciliation->system_total_cents = $systemTotals['system_total_cents'];
            $reconciliation->difference_cents = $differenceCents;
            $reconciliation->save();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mobile money reconciliation recorded.')]);

        return to_route('expenses.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function expenseListItem(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'description' => $expense->description,
            'category' => $expense->category,
            'amount_cents' => $expense->amount_cents,
            'amount_formatted' => $this->formatCents($expense->amount_cents),
            'expense_date' => $expense->expense_date->toDateString(),
            'expense_date_formatted' => $expense->expense_date->format('M j, Y'),
            'recorded_by_name' => $expense->recorder->name,
            'notes' => $expense->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function closingListItem(DailyCashClosing $closing): array
    {
        return [
            'id' => $closing->id,
            'closing_date' => $closing->closing_date->toDateString(),
            'closing_date_formatted' => $closing->closing_date->format('M j, Y'),
            'system_cash_total_cents' => $closing->system_cash_total_cents,
            'system_cash_total_formatted' => $this->formatCents($closing->system_cash_total_cents),
            'counted_cash_cents' => $closing->counted_cash_cents,
            'counted_cash_formatted' => $this->formatCents($closing->counted_cash_cents),
            'difference_cents' => $closing->difference_cents,
            'difference_formatted' => $this->formatCents($closing->difference_cents),
            'closed_by_name' => $closing->closer->name,
            'closed_at_formatted' => $closing->closed_at->format('M j, Y g:i A'),
            'notes' => $closing->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reconciliationListItem(MobileMoneyReconciliation $reconciliation): array
    {
        return [
            'id' => $reconciliation->id,
            'reconciliation_date' => $reconciliation->reconciliation_date->toDateString(),
            'reconciliation_date_formatted' => $reconciliation->reconciliation_date->format('M j, Y'),
            'provider' => $reconciliation->provider->value,
            'transaction_count' => $reconciliation->transaction_count,
            'system_total_cents' => $reconciliation->system_total_cents,
            'system_total_formatted' => $this->formatCents($reconciliation->system_total_cents),
            'provider_total_cents' => $reconciliation->provider_total_cents,
            'provider_total_formatted' => $this->formatCents($reconciliation->provider_total_cents),
            'difference_cents' => $reconciliation->difference_cents,
            'difference_formatted' => $this->formatCents($reconciliation->difference_cents),
            'status' => $reconciliation->status->value,
            'status_label' => $reconciliation->status->label(),
            'reconciled_by_name' => $reconciliation->reconciler->name,
            'reconciled_at_formatted' => $reconciliation->reconciled_at->format('M j, Y g:i A'),
            'notes' => $reconciliation->notes,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function mobileMoneyProviderOptions(): array
    {
        return [
            ['value' => 'Telesom', 'label' => 'Telesom'],
            ['value' => 'Golis', 'label' => 'Golis'],
            ['value' => 'Somtel', 'label' => 'Somtel'],
            ['value' => 'Somlink', 'label' => 'Somlink'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function expenseCategoryOptions(): array
    {
        return [
            ['value' => 'general', 'label' => 'General'],
            ['value' => 'supplies', 'label' => 'Supplies'],
            ['value' => 'utilities', 'label' => 'Utilities'],
            ['value' => 'maintenance', 'label' => 'Maintenance'],
        ];
    }

    private function formatCents(int $cents): string
    {
        $negative = $cents < 0;
        $absolute = abs($cents);

        return ($negative ? '-' : '').'$'.number_format($absolute / 100, 2);
    }
}
