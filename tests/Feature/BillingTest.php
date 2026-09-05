<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TreatmentStatus;
use App\Enums\VerificationStatus;
use App\Models\FeeItem;
use App\Models\Invoice;
use App\Models\MobileMoneyTransaction;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Treatment;
use App\Models\TreatmentProcedure;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00', 'Africa/Mogadishu'));
});

afterEach(function () {
    Carbon::setTestNow();
});

function createCompletedTreatmentWithProcedures(int $feeCents = 10000, int $quantity = 2): Treatment
{
    $feeItem = FeeItem::factory()->create([
        'price_cents' => intdiv($feeCents, $quantity),
        'tax_rate_bps' => 0,
    ]);

    $treatment = Treatment::factory()->completed()->create();

    TreatmentProcedure::factory()->create([
        'treatment_id' => $treatment->id,
        'fee_item_id' => $feeItem->id,
        'quantity' => $quantity,
        'fee_cents' => $feeCents,
    ]);

    return $treatment->fresh(['procedures.feeItem']);
}

function generateInvoiceForTreatment(Treatment $treatment, User $user): Invoice
{
    $response = test()->actingAs($user)
        ->post(route('billing.invoices.generate', $treatment));

    $response->assertRedirect();

    return Invoice::query()->where('treatment_id', $treatment->id)->firstOrFail();
}

test('guest is redirected to login when visiting billing index', function () {
    $this->get(route('billing.index'))
        ->assertRedirectToRoute('login');
});

test('lab cannot view billing module', function () {
    $user = User::factory()->lab()->create();

    $this->actingAs($user)
        ->get(route('billing.index'))
        ->assertForbidden();
});

test('nurse cannot view billing module', function () {
    $user = User::factory()->nurse()->create();

    $this->actingAs($user)
        ->get(route('billing.index'))
        ->assertForbidden();
});

test('accountant can view billing index', function () {
    $user = User::factory()->accountant()->create();

    $this->actingAs($user)
        ->get(route('billing.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('billing/Index')
            ->has('invoices'));
});

test('dentist can view billing index', function () {
    $user = User::factory()->dentist()->create();

    $this->actingAs($user)
        ->get(route('billing.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('billing/Index'));
});

test('receptionist can generate invoice from completed treatment', function () {
    $user = User::factory()->receptionist()->create();
    $treatment = createCompletedTreatmentWithProcedures(feeCents: 10000, quantity: 2);

    $this->actingAs($user)
        ->post(route('billing.invoices.generate', $treatment))
        ->assertRedirect();

    $invoice = Invoice::query()->where('treatment_id', $treatment->id)->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->invoice_number)->toMatch('/^INV-2026-\d{5}$/')
        ->and($invoice->status)->toBe(InvoiceStatus::Issued)
        ->and($invoice->subtotal_cents)->toBe(10000)
        ->and($invoice->total_cents)->toBe(10000)
        ->and($invoice->balance_cents)->toBe(10000)
        ->and($invoice->items)->toHaveCount(1)
        ->and($invoice->items->first()->unit_price_cents)->toBe(5000)
        ->and($invoice->items->first()->line_total_cents)->toBe(10000);
});

test('dentist cannot generate invoice', function () {
    $user = User::factory()->dentist()->create();
    $treatment = createCompletedTreatmentWithProcedures();

    $this->actingAs($user)
        ->post(route('billing.invoices.generate', $treatment))
        ->assertForbidden();

    expect(Invoice::query()->where('treatment_id', $treatment->id)->exists())->toBeFalse();
});

test('dentist cannot pay invoice', function () {
    $dentist = User::factory()->dentist()->create();
    $treatment = createCompletedTreatmentWithProcedures(feeCents: 5000);
    $invoice = generateInvoiceForTreatment($treatment, User::factory()->admin()->create());

    $this->actingAs($dentist)
        ->post(route('billing.payments.store', $invoice), [
            'amount' => 50.00,
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertForbidden();

    expect($invoice->fresh()->amount_paid_cents)->toBe(0);
});

test('generating invoice twice for same treatment returns 422', function () {
    $user = User::factory()->admin()->create();
    $treatment = createCompletedTreatmentWithProcedures();

    generateInvoiceForTreatment($treatment, $user);

    $this->actingAs($user)
        ->post(route('billing.invoices.generate', $treatment))
        ->assertSessionHasErrors('treatment');
});

test('generating invoice for incomplete treatment returns 422', function () {
    $user = User::factory()->admin()->create();
    $treatment = Treatment::factory()->create(['status' => TreatmentStatus::Planned]);

    TreatmentProcedure::factory()->create(['treatment_id' => $treatment->id]);

    $this->actingAs($user)
        ->post(route('billing.invoices.generate', $treatment))
        ->assertSessionHasErrors('treatment');
});

test('invoice show displays fee lines and dollar totals', function () {
    $user = User::factory()->accountant()->create();
    $treatment = createCompletedTreatmentWithProcedures(feeCents: 15000);
    $invoice = generateInvoiceForTreatment($treatment, $user);

    $this->actingAs($user)
        ->get(route('billing.show', $invoice))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('billing/Show')
            ->where('invoice.invoice_number', $invoice->invoice_number)
            ->where('invoice.total_formatted', '$150.00')
            ->where('invoice.balance_formatted', '$150.00')
            ->has('invoice.items', 1)
            ->where('invoice.items.0.line_total_formatted', '$150.00'));
});

test('partial payment updates invoice to partially paid', function () {
    $user = User::factory()->receptionist()->create();
    $treatment = createCompletedTreatmentWithProcedures(feeCents: 10000);
    $invoice = generateInvoiceForTreatment($treatment, $user);

    $this->actingAs($user)
        ->post(route('billing.payments.store', $invoice), [
            'amount' => 40.00,
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect();

    $invoice->refresh();

    expect($invoice->amount_paid_cents)->toBe(4000)
        ->and($invoice->balance_cents)->toBe(6000)
        ->and($invoice->status)->toBe(InvoiceStatus::PartiallyPaid);

    expect(Payment::query()->where('invoice_id', $invoice->id)->count())->toBe(1)
        ->and(Receipt::query()->whereHas('payment', fn ($q) => $q->where('invoice_id', $invoice->id))->count())->toBe(1);
});

test('exact payment marks invoice as paid', function () {
    $user = User::factory()->accountant()->create();
    $treatment = createCompletedTreatmentWithProcedures(feeCents: 5000);
    $invoice = generateInvoiceForTreatment($treatment, $user);

    $this->actingAs($user)
        ->post(route('billing.payments.store', $invoice), [
            'amount' => 50.00,
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect();

    $invoice->refresh();

    expect($invoice->amount_paid_cents)->toBe(5000)
        ->and($invoice->balance_cents)->toBe(0)
        ->and($invoice->status)->toBe(InvoiceStatus::Paid);
});

test('overpay returns 422', function () {
    $user = User::factory()->admin()->create();
    $treatment = createCompletedTreatmentWithProcedures(feeCents: 5000);
    $invoice = generateInvoiceForTreatment($treatment, $user);

    $this->actingAs($user)
        ->post(route('billing.payments.store', $invoice), [
            'amount' => 100.00,
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertSessionHasErrors('amount');

    $invoice->refresh();

    expect($invoice->amount_paid_cents)->toBe(0)
        ->and($invoice->status)->toBe(InvoiceStatus::Issued);
});

test('duplicate zaad transaction id returns 422', function () {
    $user = User::factory()->admin()->create();
    $treatment = createCompletedTreatmentWithProcedures(feeCents: 10000);
    $invoice = generateInvoiceForTreatment($treatment, $user);

    $payload = [
        'amount' => 50.00,
        'method' => PaymentMethod::Zaad->value,
        'reference_number' => 'ZAAD-REF-001',
        'payer_phone' => '+252631234567',
        'transaction_id' => 'ZAAD-TXN-DUPLICATE',
        'provider' => 'Telesom',
        'verification_status' => VerificationStatus::Verified->value,
    ];

    $this->actingAs($user)
        ->post(route('billing.payments.store', $invoice), $payload)
        ->assertRedirect();

    $treatment2 = createCompletedTreatmentWithProcedures(feeCents: 8000);
    $invoice2 = generateInvoiceForTreatment($treatment2, $user);

    $this->actingAs($user)
        ->post(route('billing.payments.store', $invoice2), $payload)
        ->assertSessionHasErrors('transaction_id');
});

test('verification required does not complete payment or create receipt', function () {
    $user = User::factory()->admin()->create();
    $treatment = createCompletedTreatmentWithProcedures(feeCents: 10000);
    $invoice = generateInvoiceForTreatment($treatment, $user);

    $this->actingAs($user)
        ->post(route('billing.payments.store', $invoice), [
            'amount' => 50.00,
            'method' => PaymentMethod::Zaad->value,
            'reference_number' => 'ZAAD-REF-PENDING',
            'payer_phone' => '+252631234567',
            'transaction_id' => 'ZAAD-TXN-PENDING-001',
            'provider' => 'Telesom',
            'verification_status' => VerificationStatus::VerificationRequired->value,
        ])
        ->assertRedirect(route('billing.show', $invoice));

    $invoice->refresh();
    $payment = Payment::query()->where('invoice_id', $invoice->id)->first();

    expect($invoice->amount_paid_cents)->toBe(0)
        ->and($invoice->status)->toBe(InvoiceStatus::Issued)
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->receipt)->toBeNull();

    expect(MobileMoneyTransaction::query()->where('payment_id', $payment->id)->exists())->toBeTrue();
});

test('verified zaad payment completes and creates receipt', function () {
    $user = User::factory()->admin()->create();
    $treatment = createCompletedTreatmentWithProcedures(feeCents: 10000);
    $invoice = generateInvoiceForTreatment($treatment, $user);

    $this->actingAs($user)
        ->post(route('billing.payments.store', $invoice), [
            'amount' => 100.00,
            'method' => PaymentMethod::Zaad->value,
            'reference_number' => 'ZAAD-REF-002',
            'payer_phone' => '+252631234567',
            'transaction_id' => 'ZAAD-TXN-VERIFIED-001',
            'provider' => 'Telesom',
            'verification_status' => VerificationStatus::Verified->value,
        ])
        ->assertRedirect();

    $invoice->refresh();
    $payment = Payment::query()->where('invoice_id', $invoice->id)->first();
    $receipt = Receipt::query()->where('payment_id', $payment->id)->first();

    expect($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($payment->status)->toBe(PaymentStatus::Completed)
        ->and($receipt)->not->toBeNull()
        ->and($receipt->receipt_number)->toMatch('/^RCT-2026-\d{5}$/');
});

test('receptionist can pay invoice', function () {
    $user = User::factory()->receptionist()->create();
    $treatment = createCompletedTreatmentWithProcedures(feeCents: 3000);
    $invoice = generateInvoiceForTreatment($treatment, User::factory()->admin()->create());

    $this->actingAs($user)
        ->post(route('billing.payments.store', $invoice), [
            'amount' => 30.00,
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect();
});

test('receptionist cannot refund payment', function () {
    $admin = User::factory()->admin()->create();
    $receptionist = User::factory()->receptionist()->create();
    $treatment = createCompletedTreatmentWithProcedures(feeCents: 5000);
    $invoice = generateInvoiceForTreatment($treatment, $admin);

    test()->actingAs($admin)
        ->post(route('billing.payments.store', $invoice), [
            'amount' => 50.00,
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect();

    $payment = Payment::query()->where('invoice_id', $invoice->id)->first();

    $this->actingAs($receptionist)
        ->post(route('billing.refunds.store', $invoice), [
            'original_payment_number' => $payment->payment_number,
            'amount' => 50.00,
        ])
        ->assertForbidden();
});

test('admin can refund payment referencing original payment number', function () {
    $admin = User::factory()->admin()->create();
    $treatment = createCompletedTreatmentWithProcedures(feeCents: 10000);
    $invoice = generateInvoiceForTreatment($treatment, $admin);

    test()->actingAs($admin)
        ->post(route('billing.payments.store', $invoice), [
            'amount' => 100.00,
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect();

    $payment = Payment::query()->where('invoice_id', $invoice->id)->first();

    $this->actingAs($admin)
        ->post(route('billing.refunds.store', $invoice), [
            'original_payment_number' => $payment->payment_number,
            'amount' => 100.00,
        ])
        ->assertRedirect(route('billing.show', $invoice));

    $invoice->refresh();

    expect($invoice->amount_paid_cents)->toBe(0)
        ->and($invoice->balance_cents)->toBe(10000)
        ->and($invoice->status)->toBe(InvoiceStatus::Refunded);

    expect(Payment::query()->where('invoice_id', $invoice->id)->where('status', PaymentStatus::Refunded)->exists())->toBeTrue();
});

test('refund exceeding original payment amount returns 422', function () {
    $admin = User::factory()->admin()->create();
    $treatment = createCompletedTreatmentWithProcedures(feeCents: 5000);
    $invoice = generateInvoiceForTreatment($treatment, $admin);

    test()->actingAs($admin)
        ->post(route('billing.payments.store', $invoice), [
            'amount' => 50.00,
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect();

    $payment = Payment::query()->where('invoice_id', $invoice->id)->first();

    $this->actingAs($admin)
        ->post(route('billing.refunds.store', $invoice), [
            'original_payment_number' => $payment->payment_number,
            'amount' => 60.00,
        ])
        ->assertSessionHasErrors('amount');

    expect($invoice->fresh()->amount_paid_cents)->toBe(5000);
});

test('double refund against same payment returns 422 on second attempt', function () {
    $admin = User::factory()->admin()->create();
    $treatment = createCompletedTreatmentWithProcedures(feeCents: 10000);
    $invoice = generateInvoiceForTreatment($treatment, $admin);

    test()->actingAs($admin)
        ->post(route('billing.payments.store', $invoice), [
            'amount' => 50.00,
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect();

    test()->actingAs($admin)
        ->post(route('billing.payments.store', $invoice), [
            'amount' => 50.00,
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect();

    $firstPayment = Payment::query()
        ->where('invoice_id', $invoice->id)
        ->where('status', PaymentStatus::Completed)
        ->orderBy('id')
        ->first();

    $this->actingAs($admin)
        ->post(route('billing.refunds.store', $invoice), [
            'original_payment_number' => $firstPayment->payment_number,
            'amount' => 50.00,
        ])
        ->assertRedirect(route('billing.show', $invoice));

    $this->actingAs($admin)
        ->post(route('billing.refunds.store', $invoice), [
            'original_payment_number' => $firstPayment->payment_number,
            'amount' => 50.00,
        ])
        ->assertSessionHasErrors('amount');

    $invoice->refresh();

    expect($invoice->amount_paid_cents)->toBe(5000)
        ->and(Payment::query()
            ->where('invoice_id', $invoice->id)
            ->where('status', PaymentStatus::Refunded)
            ->where('reference_number', $firstPayment->payment_number)
            ->count())->toBe(1);
});

test('sequential refunds cannot drive amount paid below zero', function () {
    $admin = User::factory()->admin()->create();
    $treatment = createCompletedTreatmentWithProcedures(feeCents: 10000);
    $invoice = generateInvoiceForTreatment($treatment, $admin);

    test()->actingAs($admin)
        ->post(route('billing.payments.store', $invoice), [
            'amount' => 100.00,
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect();

    $payment = Payment::query()->where('invoice_id', $invoice->id)->first();

    $this->actingAs($admin)
        ->post(route('billing.refunds.store', $invoice), [
            'original_payment_number' => $payment->payment_number,
            'amount' => 60.00,
        ])
        ->assertRedirect(route('billing.show', $invoice));

    $this->actingAs($admin)
        ->post(route('billing.refunds.store', $invoice), [
            'original_payment_number' => $payment->payment_number,
            'amount' => 60.00,
        ])
        ->assertSessionHasErrors('amount');

    $invoice->refresh();

    expect($invoice->amount_paid_cents)->toBe(4000)
        ->and($invoice->amount_paid_cents)->toBeGreaterThanOrEqual(0);
});

test('invoice show exposes zero remaining refundable cents after full refund', function () {
    $admin = User::factory()->admin()->create();
    $treatment = createCompletedTreatmentWithProcedures(feeCents: 5000);
    $invoice = generateInvoiceForTreatment($treatment, $admin);

    test()->actingAs($admin)
        ->post(route('billing.payments.store', $invoice), [
            'amount' => 50.00,
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect();

    $payment = Payment::query()->where('invoice_id', $invoice->id)->firstOrFail();

    test()->actingAs($admin)
        ->post(route('billing.refunds.store', $invoice), [
            'original_payment_number' => $payment->payment_number,
            'amount' => 50.00,
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->get(route('billing.show', $invoice))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('billing/Show')
            ->where('invoice.payments', fn ($payments) => collect($payments)->contains(
                fn (array $row): bool => $row['payment_number'] === $payment->payment_number
                    && $row['remaining_refundable_cents'] === 0,
            )));
});

test('card payment requires reference number', function () {
    $user = User::factory()->admin()->create();
    $treatment = createCompletedTreatmentWithProcedures(feeCents: 5000);
    $invoice = generateInvoiceForTreatment($treatment, $user);

    $this->actingAs($user)
        ->post(route('billing.payments.store', $invoice), [
            'amount' => 50.00,
            'method' => PaymentMethod::Card->value,
        ])
        ->assertSessionHasErrors('reference_number');
});
