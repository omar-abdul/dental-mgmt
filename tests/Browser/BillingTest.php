<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\TreatmentStatus;
use App\Models\FeeItem;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Treatment;
use App\Models\TreatmentProcedure;
use App\Models\User;

test('receptionist can generate an invoice pay cash and open the receipt', function () {
    $receptionist = User::factory()->receptionist()->create();
    $feeItem = FeeItem::factory()->create([
        'name' => 'Consultation',
        'price_cents' => 2000,
        'tax_rate_bps' => 0,
    ]);
    $treatment = Treatment::factory()->completed()->create([
        'diagnosis' => 'Routine exam',
        'status' => TreatmentStatus::Completed,
    ]);
    TreatmentProcedure::factory()->create([
        'treatment_id' => $treatment->id,
        'fee_item_id' => $feeItem->id,
        'quantity' => 1,
        'fee_cents' => 2000,
    ]);

    $this->actingAs($receptionist);

    $page = visit(route('treatments.show', $treatment));

    $page->assertSee('Routine exam')
        ->click('@generate-invoice-button')
        ->assertSee('INV-')
        ->assertSee('$20.00')
        ->assertSee('No payments recorded yet.')
        ->click('@record-payment-button')
        ->assertSee('Record payment')
        ->click('@submit-payment-button')
        ->assertSee('Payment Receipt')
        ->assertSee('RCT-')
        ->assertSee('$20.00')
        ->assertNoJavaScriptErrors();

    $invoice = Invoice::query()->where('treatment_id', $treatment->id)->first();
    $payment = Payment::query()->first();
    $receipt = Receipt::query()->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->total_cents)->toBe(2000)
        ->and($invoice->balance_cents)->toBe(0)
        ->and($payment)->not->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::Completed)
        ->and($payment->amount_cents)->toBe(2000)
        ->and($receipt)->not->toBeNull()
        ->and($receipt->receipt_number)->toMatch('/^RCT-\d{4}-\d{5}$/');
});

test('billing index empty state is usable', function () {
    $accountant = User::factory()->accountant()->create();

    $this->actingAs($accountant);

    $page = visit(route('billing.index'));

    $page->assertSee('Billing')
        ->assertSee('No invoices found.')
        ->assertNoJavaScriptErrors();
});
