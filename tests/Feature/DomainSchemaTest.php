<?php

use App\Enums\InventoryCategory;
use App\Enums\InvoiceStatus;
use App\Enums\PatientStatus;
use App\Enums\PaymentMethod;
use App\Enums\VerificationStatus;
use App\Models\Appointment;
use App\Models\Chair;
use App\Models\Dentist;
use App\Models\FeeItem;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\MobileMoneyTransaction;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Treatment;
use App\Models\WorkingHour;

test('domain schema factory graph persists related aggregates with DCMS formats', function () {
    $patient = Patient::factory()->create();
    $room = Room::factory()->create(['name' => 'Operatory 1']);
    $chair = Chair::factory()->for($room)->create();
    $dentist = Dentist::factory()->withDefaultChair($chair)->create();
    $feeItem = FeeItem::factory()->create([
        'code' => 'EXAM-TEST',
        'price_cents' => 2500,
    ]);

    $appointment = Appointment::factory()
        ->for($patient)
        ->for($dentist)
        ->for($chair)
        ->create([
            'fee_item_id' => $feeItem->id,
        ]);

    $treatment = Treatment::factory()
        ->for($patient)
        ->for($dentist)
        ->create(['appointment_id' => $appointment->id]);

    $invoice = Invoice::factory()
        ->for($patient)
        ->forTreatment($treatment)
        ->withAmounts(subtotalCents: 2500, amountPaidCents: 2500)
        ->create(['status' => InvoiceStatus::Paid]);

    $payment = Payment::factory()
        ->forInvoice($invoice)
        ->zaad()
        ->create(['amount_cents' => 2500]);

    $inventoryItem = InventoryItem::factory()->create([
        'name' => 'Composite Resin',
        'category' => InventoryCategory::DentalMaterials,
        'unit_cost_cents' => 1500,
    ]);

    expect($patient->patient_number)->toMatch('/^PAT-\d{4}-\d{5}$/');
    expect($appointment->number)->toMatch('/^APT-\d{4}-\d{5}$/');
    expect($invoice->invoice_number)->toMatch('/^INV-\d{4}-\d{5}$/');
    expect($payment->payment_number)->toMatch('/^PAY-\d{4}-\d{5}$/');
    expect($payment->method)->toBe(PaymentMethod::Zaad);
    expect($invoice->subtotal_cents)->toBe(2500);
    expect($invoice->total_cents)->toBe(2500);
    expect($invoice->balance_cents)->toBe(0);
    expect($payment->amount_cents)->toBe(2500);

    $this->assertModelExists($patient);
    $this->assertModelExists($room);
    $this->assertModelExists($chair);
    $this->assertModelExists($dentist);
    $this->assertModelExists($appointment);
    $this->assertModelExists($treatment);
    $this->assertModelExists($invoice);
    $this->assertModelExists($payment);
    $this->assertModelExists($inventoryItem);

    expect($appointment->patient->is($patient))->toBeTrue();
    expect($appointment->dentist->is($dentist))->toBeTrue();
    expect($appointment->chair->is($chair))->toBeTrue();
    expect($treatment->appointment->is($appointment))->toBeTrue();
    expect($invoice->treatment->is($treatment))->toBeTrue();
    expect($payment->invoice->is($invoice))->toBeTrue();
    expect($dentist->user->dentist->is($dentist))->toBeTrue();

    $mobileTxn = MobileMoneyTransaction::query()->where('payment_id', $payment->id)->first();
    expect($mobileTxn)->not->toBeNull();
    expect($mobileTxn->transaction_id)->not->toBeEmpty();
    expect($mobileTxn->verification_status)->toBe(VerificationStatus::Verified);
    expect($mobileTxn->verified_by)->toBe($payment->received_by);
    expect($mobileTxn->verified_at)->not->toBeNull();
    $this->assertDatabaseHas('mobile_money_transactions', [
        'payment_id' => $payment->id,
        'transaction_id' => $mobileTxn->transaction_id,
    ]);
});

test('archived patient factory state soft-deletes and excludes from default queries', function () {
    $archivedPatient = Patient::factory()->archived()->create();

    expect($archivedPatient->status)->toBe(PatientStatus::Archived);
    expect($archivedPatient->deleted_at)->not->toBeNull();
    expect(Patient::query()->whereKey($archivedPatient->id)->exists())->toBeFalse();
    expect(Patient::withTrashed()->whereKey($archivedPatient->id)->exists())->toBeTrue();
});

test('payment factory derives patient_id from invoice by default', function () {
    $invoice = Invoice::factory()->create();
    $payment = Payment::factory()->create(['invoice_id' => $invoice->id]);

    expect($payment->patient_id)->toBe($invoice->patient_id);
});

test('database seeder inserts nine fee items and seven working hours with friday closed', function () {
    $this->seed();

    expect(FeeItem::query()->count())->toBe(9);
    expect(WorkingHour::query()->count())->toBe(7);

    $exam = FeeItem::query()->where('code', 'EXAM')->first();
    expect($exam)->not->toBeNull();
    expect($exam->price_cents)->toBe(2500);

    $friday = WorkingHour::query()->where('weekday', 5)->first();
    expect($friday)->not->toBeNull();
    expect($friday->opens_at)->toBeNull();
    expect($friday->closes_at)->toBeNull();
});
