<?php

namespace App\Http\Controllers;

use App\Enums\InstallmentStatus;
use App\Enums\PaymentPlanStatus;
use App\Http\Requests\StorePaymentPlanRequest;
use App\Models\Installment;
use App\Models\Invoice;
use App\Models\PaymentPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PaymentPlanController extends Controller
{
    public function store(StorePaymentPlanRequest $request, Invoice $invoice): RedirectResponse
    {
        Gate::authorize('create', PaymentPlan::class);

        $user = $request->user();

        abort_unless($user !== null, 403);

        $installments = $request->installmentData();
        $totalCents = array_sum(array_column($installments, 'amount_cents'));

        DB::transaction(function () use ($invoice, $installments, $totalCents, $user): void {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $existingAllocatedCents = PaymentPlan::activeAllocatedCentsForInvoice($lockedInvoice->id);

            if ($totalCents + $existingAllocatedCents > $lockedInvoice->balance_cents) {
                throw ValidationException::withMessages([
                    'installments' => __('Installment allocations cannot exceed the remaining invoice balance.'),
                ]);
            }

            $plan = PaymentPlan::query()->create([
                'invoice_id' => $lockedInvoice->id,
                'total_cents' => $totalCents,
                'status' => PaymentPlanStatus::Active,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach ($installments as $installment) {
                Installment::query()->create([
                    'payment_plan_id' => $plan->id,
                    'amount_cents' => $installment['amount_cents'],
                    'due_date' => $installment['due_date'],
                    'status' => InstallmentStatus::Pending,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment plan created.')]);

        return to_route('billing.show', $invoice);
    }
}
