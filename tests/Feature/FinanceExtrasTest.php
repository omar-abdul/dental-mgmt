<?php

use App\Enums\InsuranceClaimStatus;
use App\Enums\MobileMoneyProvider;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReconciliationStatus;
use App\Enums\VerificationStatus;
use App\Models\DailyCashClosing;
use App\Models\Expense;
use App\Models\InsuranceClaim;
use App\Models\Invoice;
use App\Models\MobileMoneyReconciliation;
use App\Models\MobileMoneyTransaction;
use App\Models\Payment;
use App\Models\PaymentPlan;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-09-04 15:00:00', 'Africa/Mogadishu'));
});

afterEach(function () {
    Carbon::setTestNow();
});

test('guest is redirected to login when visiting expenses index', function () {
    $this->get(route('expenses.index'))
        ->assertRedirectToRoute('login');
});

test('accountant can view expenses index', function () {
    $accountant = User::factory()->accountant()->create();

    $this->actingAs($accountant)
        ->get(route('expenses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('expenses/Index')
            ->has('expenses')
            ->where('canCreate', true)
            ->where('canCloseCash', true));
});

test('admin can record an expense', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('expenses.store'), [
            'description' => 'Office supplies',
            'category' => 'supplies',
            'amount' => '45.50',
            'expense_date' => '2026-09-04',
            'notes' => 'Printer paper',
        ])
        ->assertRedirect(route('expenses.index'));

    $expense = Expense::query()->first();

    expect($expense)->not->toBeNull()
        ->and($expense->description)->toBe('Office supplies')
        ->and($expense->amount_cents)->toBe(4550)
        ->and($expense->recorded_by)->toBe($admin->id);
});

test('dentist cannot view expenses index', function () {
    $dentist = User::factory()->dentist()->create();

    $this->actingAs($dentist)
        ->get(route('expenses.index'))
        ->assertForbidden();
});

test('dentist cannot record an expense', function () {
    $dentist = User::factory()->dentist()->create();

    $this->actingAs($dentist)
        ->post(route('expenses.store'), [
            'description' => 'Blocked expense',
            'category' => 'general',
            'amount' => '10.00',
            'expense_date' => '2026-09-04',
        ])
        ->assertForbidden();

    expect(Expense::query()->count())->toBe(0);
});

test('accountant can record daily cash closing with system total from completed cash payments', function () {
    $accountant = User::factory()->accountant()->create();
    $invoice = Invoice::factory()->withAmounts(subtotalCents: 10000)->create();

    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'patient_id' => $invoice->patient_id,
        'amount_cents' => 6000,
        'method' => PaymentMethod::Cash,
        'status' => PaymentStatus::Completed,
        'paid_at' => now(),
        'received_by' => $accountant->id,
    ]);

    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'patient_id' => $invoice->patient_id,
        'amount_cents' => 4000,
        'method' => PaymentMethod::Cash,
        'status' => PaymentStatus::Completed,
        'paid_at' => now(),
        'received_by' => $accountant->id,
    ]);

    $this->actingAs($accountant)
        ->post(route('expenses.daily-closings.store'), [
            'closing_date' => '2026-09-04',
            'counted_cash' => '100.50',
            'notes' => 'Till count',
        ])
        ->assertRedirect(route('expenses.index'));

    $closing = DailyCashClosing::query()->first();

    expect($closing)->not->toBeNull()
        ->and($closing->system_cash_total_cents)->toBe(10000)
        ->and($closing->counted_cash_cents)->toBe(10050)
        ->and($closing->difference_cents)->toBe(50)
        ->and($closing->closed_by)->toBe($accountant->id);
});

test('dentist cannot record daily cash closing', function () {
    $dentist = User::factory()->dentist()->create();

    $this->actingAs($dentist)
        ->post(route('expenses.daily-closings.store'), [
            'closing_date' => '2026-09-04',
            'counted_cash' => '50.00',
        ])
        ->assertForbidden();

    expect(DailyCashClosing::query()->count())->toBe(0);
});

test('payment plan allocations cannot exceed remaining invoice balance', function () {
    $accountant = User::factory()->accountant()->create();
    $invoice = Invoice::factory()->withAmounts(subtotalCents: 10000)->create();

    $this->actingAs($accountant)
        ->post(route('billing.payment-plans.store', $invoice), [
            'installments' => [
                ['amount' => '60.00', 'due_date' => '2026-10-01'],
                ['amount' => '50.00', 'due_date' => '2026-11-01'],
            ],
        ])
        ->assertSessionHasErrors('installments');

    expect(PaymentPlan::query()->count())->toBe(0);
});

test('accountant can create payment plan within invoice balance', function () {
    $accountant = User::factory()->accountant()->create();
    $invoice = Invoice::factory()->withAmounts(subtotalCents: 10000)->create();

    $this->actingAs($accountant)
        ->post(route('billing.payment-plans.store', $invoice), [
            'installments' => [
                ['amount' => '60.00', 'due_date' => '2026-10-01'],
                ['amount' => '40.00', 'due_date' => '2026-11-01'],
            ],
        ])
        ->assertRedirect(route('billing.show', $invoice));

    $plan = PaymentPlan::query()->with('installments')->first();

    expect($plan)->not->toBeNull()
        ->and($plan->total_cents)->toBe(10000)
        ->and($plan->installments)->toHaveCount(2)
        ->and($plan->installments->sum('amount_cents'))->toBe(10000);
});

test('accountant can record insurance claim stub with provider and status', function () {
    $accountant = User::factory()->accountant()->create();
    $invoice = Invoice::factory()->withAmounts(subtotalCents: 15000)->create();

    $this->actingAs($accountant)
        ->post(route('billing.insurance-claims.store', $invoice), [
            'provider' => 'Golis Health Insurance',
            'status' => InsuranceClaimStatus::Submitted->value,
        ])
        ->assertRedirect(route('billing.show', $invoice));

    $claim = InsuranceClaim::query()->first();

    expect($claim)->not->toBeNull()
        ->and($claim->provider)->toBe('Golis Health Insurance')
        ->and($claim->status)->toBe(InsuranceClaimStatus::Submitted)
        ->and($claim->invoice_id)->toBe($invoice->id);
});

test('mobile money reconciliation stores system totals versus entered provider total', function () {
    $accountant = User::factory()->accountant()->create();
    $invoice = Invoice::factory()->withAmounts(subtotalCents: 20000)->create();

    $payment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'patient_id' => $invoice->patient_id,
        'amount_cents' => 7500,
        'method' => PaymentMethod::Zaad,
        'status' => PaymentStatus::Completed,
        'paid_at' => now(),
        'received_by' => $accountant->id,
    ]);

    MobileMoneyTransaction::factory()->create([
        'payment_id' => $payment->id,
        'provider' => MobileMoneyProvider::Telesom,
        'verification_status' => VerificationStatus::Verified,
        'verified_by' => $accountant->id,
        'verified_at' => now(),
    ]);

    $this->actingAs($accountant)
        ->post(route('expenses.mobile-money-reconciliations.store'), [
            'reconciliation_date' => '2026-09-04',
            'provider' => MobileMoneyProvider::Telesom->value,
            'provider_total' => '80.00',
            'notes' => 'Provider statement higher',
        ])
        ->assertRedirect(route('expenses.index'));

    $reconciliation = MobileMoneyReconciliation::query()->first();

    expect($reconciliation)->not->toBeNull()
        ->and($reconciliation->transaction_count)->toBe(1)
        ->and($reconciliation->system_total_cents)->toBe(7500)
        ->and($reconciliation->provider_total_cents)->toBe(8000)
        ->and($reconciliation->difference_cents)->toBe(500)
        ->and($reconciliation->status)->toBe(ReconciliationStatus::Discrepancy)
        ->and($reconciliation->reconciled_by)->toBe($accountant->id);
});

test('daily cash closing nets same-day cash refunds against completed inflows', function () {
    $accountant = User::factory()->accountant()->create();
    $invoice = Invoice::factory()->withAmounts(subtotalCents: 10000)->create();

    $originalPayment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'patient_id' => $invoice->patient_id,
        'payment_number' => 'PAY-CASH-001',
        'amount_cents' => 10000,
        'method' => PaymentMethod::Cash,
        'status' => PaymentStatus::Completed,
        'paid_at' => now(),
        'received_by' => $accountant->id,
    ]);

    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'patient_id' => $invoice->patient_id,
        'payment_number' => 'PAY-CASH-REF-001',
        'amount_cents' => 3000,
        'method' => PaymentMethod::Cash,
        'status' => PaymentStatus::Refunded,
        'paid_at' => now(),
        'received_by' => $accountant->id,
        'reference_number' => $originalPayment->payment_number,
    ]);

    $this->actingAs($accountant)
        ->post(route('expenses.daily-closings.store'), [
            'closing_date' => '2026-09-04',
            'counted_cash' => '70.00',
        ])
        ->assertRedirect(route('expenses.index'));

    $closing = DailyCashClosing::query()->first();

    expect($closing)->not->toBeNull()
        ->and($closing->system_cash_total_cents)->toBe(7000)
        ->and($closing->difference_cents)->toBe(0);
});

test('mobile money reconciliation nets same-day refunds for the provider', function () {
    $accountant = User::factory()->accountant()->create();
    $invoice = Invoice::factory()->withAmounts(subtotalCents: 20000)->create();

    $originalPayment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'patient_id' => $invoice->patient_id,
        'payment_number' => 'PAY-MM-001',
        'amount_cents' => 7500,
        'method' => PaymentMethod::Zaad,
        'status' => PaymentStatus::Completed,
        'paid_at' => now(),
        'received_by' => $accountant->id,
    ]);

    MobileMoneyTransaction::factory()->create([
        'payment_id' => $originalPayment->id,
        'provider' => MobileMoneyProvider::Telesom,
        'verification_status' => VerificationStatus::Verified,
        'verified_by' => $accountant->id,
        'verified_at' => now(),
    ]);

    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'patient_id' => $invoice->patient_id,
        'payment_number' => 'PAY-MM-REF-001',
        'amount_cents' => 2500,
        'method' => PaymentMethod::Zaad,
        'status' => PaymentStatus::Refunded,
        'paid_at' => now(),
        'received_by' => $accountant->id,
        'reference_number' => $originalPayment->payment_number,
    ]);

    $this->actingAs($accountant)
        ->post(route('expenses.mobile-money-reconciliations.store'), [
            'reconciliation_date' => '2026-09-04',
            'provider' => MobileMoneyProvider::Telesom->value,
            'provider_total' => '50.00',
        ])
        ->assertRedirect(route('expenses.index'));

    $reconciliation = MobileMoneyReconciliation::query()->first();

    expect($reconciliation)->not->toBeNull()
        ->and($reconciliation->system_total_cents)->toBe(5000)
        ->and($reconciliation->difference_cents)->toBe(0);
});

test('cumulative active payment plans cannot exceed invoice balance', function () {
    $accountant = User::factory()->accountant()->create();
    $invoice = Invoice::factory()->withAmounts(subtotalCents: 10000)->create();

    $this->actingAs($accountant)
        ->post(route('billing.payment-plans.store', $invoice), [
            'installments' => [
                ['amount' => '60.00', 'due_date' => '2026-10-01'],
            ],
        ])
        ->assertRedirect(route('billing.show', $invoice));

    $this->actingAs($accountant)
        ->post(route('billing.payment-plans.store', $invoice), [
            'installments' => [
                ['amount' => '50.00', 'due_date' => '2026-11-01'],
            ],
        ])
        ->assertSessionHasErrors('installments');

    expect(PaymentPlan::query()->count())->toBe(1);
});

test('expense amount with extra decimal places is rejected', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('expenses.store'), [
            'description' => 'Invalid precision',
            'category' => 'general',
            'amount' => '10.999',
            'expense_date' => '2026-09-04',
        ])
        ->assertSessionHasErrors('amount');

    expect(Expense::query()->count())->toBe(0);
});

test('dentist cannot post mobile money reconciliation', function () {
    $dentist = User::factory()->dentist()->create();

    $this->actingAs($dentist)
        ->post(route('expenses.mobile-money-reconciliations.store'), [
            'reconciliation_date' => '2026-09-04',
            'provider' => MobileMoneyProvider::Telesom->value,
            'provider_total' => '50.00',
        ])
        ->assertForbidden();

    expect(MobileMoneyReconciliation::query()->count())->toBe(0);
});

test('receptionist cannot post mobile money reconciliation', function () {
    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist)
        ->post(route('expenses.mobile-money-reconciliations.store'), [
            'reconciliation_date' => '2026-09-04',
            'provider' => MobileMoneyProvider::Telesom->value,
            'provider_total' => '50.00',
        ])
        ->assertForbidden();

    expect(MobileMoneyReconciliation::query()->count())->toBe(0);
});

test('dentist cannot create payment plan', function () {
    $dentist = User::factory()->dentist()->create();
    $invoice = Invoice::factory()->withAmounts(subtotalCents: 10000)->create();

    $this->actingAs($dentist)
        ->post(route('billing.payment-plans.store', $invoice), [
            'installments' => [
                ['amount' => '50.00', 'due_date' => '2026-10-01'],
            ],
        ])
        ->assertForbidden();

    expect(PaymentPlan::query()->count())->toBe(0);
});

test('receptionist cannot create payment plan', function () {
    $receptionist = User::factory()->receptionist()->create();
    $invoice = Invoice::factory()->withAmounts(subtotalCents: 10000)->create();

    $this->actingAs($receptionist)
        ->post(route('billing.payment-plans.store', $invoice), [
            'installments' => [
                ['amount' => '50.00', 'due_date' => '2026-10-01'],
            ],
        ])
        ->assertForbidden();

    expect(PaymentPlan::query()->count())->toBe(0);
});

test('dentist cannot create insurance claim', function () {
    $dentist = User::factory()->dentist()->create();
    $invoice = Invoice::factory()->withAmounts(subtotalCents: 15000)->create();

    $this->actingAs($dentist)
        ->post(route('billing.insurance-claims.store', $invoice), [
            'provider' => 'Golis Health Insurance',
            'status' => InsuranceClaimStatus::Submitted->value,
        ])
        ->assertForbidden();

    expect(InsuranceClaim::query()->count())->toBe(0);
});

test('receptionist cannot create insurance claim', function () {
    $receptionist = User::factory()->receptionist()->create();
    $invoice = Invoice::factory()->withAmounts(subtotalCents: 15000)->create();

    $this->actingAs($receptionist)
        ->post(route('billing.insurance-claims.store', $invoice), [
            'provider' => 'Golis Health Insurance',
            'status' => InsuranceClaimStatus::Submitted->value,
        ])
        ->assertForbidden();

    expect(InsuranceClaim::query()->count())->toBe(0);
});

test('duplicate daily cash closing for the same date returns validation error', function () {
    $accountant = User::factory()->accountant()->create();

    $payload = [
        'closing_date' => '2026-09-04',
        'counted_cash' => '50.00',
    ];

    $this->actingAs($accountant)
        ->post(route('expenses.daily-closings.store'), $payload)
        ->assertRedirect(route('expenses.index'));

    $this->actingAs($accountant)
        ->post(route('expenses.daily-closings.store'), $payload)
        ->assertSessionHasErrors('closing_date');

    expect(DailyCashClosing::query()->count())->toBe(1);
});
