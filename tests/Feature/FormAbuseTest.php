<?php

use App\Enums\ClinicRole;
use App\Enums\Gender;
use App\Enums\ImagingOrderType;
use App\Enums\InsuranceClaimStatus;
use App\Enums\InventoryCategory;
use App\Enums\InventoryMovementType;
use App\Enums\LabOrderStatus;
use App\Enums\MobileMoneyProvider;
use App\Enums\PaymentMethod;
use App\Enums\TreatmentPlanItemAcceptance;
use App\Enums\TreatmentStatus;
use App\Models\CommunicationTemplate;
use App\Models\Dentist;
use App\Models\Encounter;
use App\Models\Expense;
use App\Models\FeeItem;
use App\Models\InsuranceClaim;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Treatment;
use App\Models\TreatmentPlan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\Support\ClinicSurface;

function coveragePatientPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Valid',
        'last_name' => 'Person',
        'date_of_birth' => '1990-05-15',
        'gender' => Gender::Female->value,
        'phone' => '+252611234567',
        'email' => 'valid.person@example.com',
    ], $overrides);
}

test('guests cannot submit authenticated mutations', function (string $method, Closure $url) {
    $this->{$method}($url())
        ->assertRedirectToRoute('login');
})->with([
    'patients store' => ['post', fn () => route('patients.store')],
    'patients update' => ['put', fn () => route('patients.update', ClinicSurface::records()['patient'])],
    'patients archive' => ['post', fn () => route('patients.archive', ClinicSurface::records()['patient'])],
    'appointments store' => ['post', fn () => route('appointments.store')],
    'appointments update' => ['put', fn () => route('appointments.update', ClinicSurface::records()['appointment'])],
    'appointments cancel' => ['post', fn () => route('appointments.cancel', ClinicSurface::records()['appointment'])],
    'appointments check-in' => ['post', fn () => route('appointments.check-in', ClinicSurface::records()['appointment'])],
    'treatments store' => ['post', fn () => route('treatments.store')],
    'treatments complete' => ['post', fn () => route('treatments.complete', ClinicSurface::records()['treatment'])],
    'invoice generate' => ['post', fn () => route('billing.invoices.generate', ClinicSurface::records()['treatment'])],
    'payments store' => ['post', fn () => route('billing.payments.store', ClinicSurface::records()['invoice'])],
    'refunds store' => ['post', fn () => route('billing.refunds.store', ClinicSurface::records()['invoice'])],
    'payment plans store' => ['post', fn () => route('billing.payment-plans.store', ClinicSurface::records()['invoice'])],
    'insurance claims store' => ['post', fn () => route('billing.insurance-claims.store', ClinicSurface::records()['invoice'])],
    'expenses store' => ['post', fn () => route('expenses.store')],
    'cash closings store' => ['post', fn () => route('expenses.daily-closings.store')],
    'mm recon store' => ['post', fn () => route('expenses.mobile-money-reconciliations.store')],
    'lab store' => ['post', fn () => route('lab.store')],
    'lab transition' => ['post', fn () => route('lab.transition', ClinicSurface::records()['labOrder'])],
    'imaging store' => ['post', fn () => route('imaging.store')],
    'inventory store' => ['post', fn () => route('inventory.store')],
    'inventory adjust' => ['post', fn () => route('inventory.adjust', ClinicSurface::records()['inventoryItem'])],
    'suppliers store' => ['post', fn () => route('inventory.suppliers.store')],
    'purchase orders store' => ['post', fn () => route('inventory.purchase-orders.store')],
    'purchase orders receive' => ['post', fn () => route('inventory.purchase-orders.receive', ClinicSurface::records()['purchaseOrder'])],
    'odontogram update' => ['patch', fn () => route('patients.odontogram.update', ClinicSurface::records()['patient'])],
    'chart plans store' => ['post', fn () => route('patients.chart.plans.store', ClinicSurface::records()['patient'])],
    'plan items store' => ['post', fn () => route('treatment-plans.items.store', ClinicSurface::records()['treatmentPlan'])],
    'plan items update' => ['patch', function () {
        $records = ClinicSurface::records();

        return route('treatment-plans.items.update', [$records['treatmentPlan'], $records['planItem']]);
    }],
    'soap update' => ['patch', fn () => route('encounters.soap.update', ClinicSurface::records()['encounter'])],
    'encounter sign' => ['post', fn () => route('encounters.sign', ClinicSurface::records()['encounter'])],
    'amendments store' => ['post', fn () => route('encounters.amendments.store', ClinicSurface::records()['encounter'])],
    'staff store' => ['post', fn () => route('staff.store')],
    'profile update' => ['patch', fn () => route('profile.update')],
    'profile destroy' => ['delete', fn () => route('profile.destroy')],
    'password update' => ['put', fn () => route('user-password.update')],
    'notification templates update' => ['patch', fn () => route('notification-templates.update', ClinicSurface::records()['template'])],
]);

test('empty payloads are rejected on forms with required fields', function (string $method, Closure $url, array $keys) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->{$method}($url(), [])
        ->assertInvalid($keys);
})->with([
    'patients store' => ['post', fn () => route('patients.store'), ['first_name', 'last_name', 'date_of_birth', 'gender', 'phone']],
    'patients update' => ['put', fn () => route('patients.update', ClinicSurface::records()['patient']), ['first_name', 'last_name', 'date_of_birth', 'gender', 'phone']],
    'appointments store' => ['post', fn () => route('appointments.store'), ['patient_id', 'dentist_id', 'chair_id', 'starts_at']],
    'treatments store' => ['post', fn () => route('treatments.store'), ['patient_id', 'dentist_id', 'diagnosis', 'procedures', 'prescription_items']],
    'payments store' => ['post', fn () => route('billing.payments.store', ClinicSurface::records()['invoice']), ['amount', 'method']],
    'refunds store' => ['post', fn () => route('billing.refunds.store', ClinicSurface::records()['invoice']), ['original_payment_number', 'amount']],
    'payment plans store' => ['post', fn () => route('billing.payment-plans.store', ClinicSurface::records()['invoice']), ['installments']],
    'insurance claims store' => ['post', fn () => route('billing.insurance-claims.store', ClinicSurface::records()['invoice']), ['provider', 'status']],
    'expenses store' => ['post', fn () => route('expenses.store'), ['description', 'category', 'amount', 'expense_date']],
    'cash closings store' => ['post', fn () => route('expenses.daily-closings.store'), ['closing_date', 'counted_cash']],
    'mm recon store' => ['post', fn () => route('expenses.mobile-money-reconciliations.store'), ['reconciliation_date', 'provider', 'provider_total']],
    'lab store' => ['post', fn () => route('lab.store'), ['patient_id', 'dentist_id', 'description']],
    'lab transition' => ['post', fn () => route('lab.transition', ClinicSurface::records()['labOrder']), ['status']],
    'imaging store' => ['post', fn () => route('imaging.store'), ['patient_id', 'dentist_id', 'type']],
    'inventory store' => ['post', fn () => route('inventory.store'), ['name', 'category', 'quantity', 'unit', 'reorder_level', 'unit_cost']],
    'inventory adjust' => ['post', fn () => route('inventory.adjust', ClinicSurface::records()['inventoryItem']), ['type', 'quantity']],
    'suppliers store' => ['post', fn () => route('inventory.suppliers.store'), ['name']],
    'purchase orders store' => ['post', fn () => route('inventory.purchase-orders.store'), ['supplier_id', 'items']],
    'odontogram update' => ['patch', fn () => route('patients.odontogram.update', ClinicSurface::records()['patient']), ['tooth_fdi', 'status']],
    'chart plans store' => ['post', fn () => route('patients.chart.plans.store', ClinicSurface::records()['patient']), ['dentist_id']],
    'plan items store' => ['post', fn () => route('treatment-plans.items.store', ClinicSurface::records()['treatmentPlan']), ['description', 'fee_cents']],
    'plan items update' => ['patch', function () {
        $records = ClinicSurface::records();

        return route('treatment-plans.items.update', [$records['treatmentPlan'], $records['planItem']]);
    }, ['acceptance_status']],
    'amendments store' => ['post', fn () => route('encounters.amendments.store', ClinicSurface::signedEncounter()), ['body']],
    'staff store' => ['post', fn () => route('staff.store'), ['name', 'email', 'role', 'password']],
]);

test('creating a patient ignores forged identifiers and stores xss as text', function () {
    $admin = User::factory()->admin()->create();
    $xss = '<script>alert(1)</script>';

    $this->actingAs($admin)
        ->post(route('patients.store'), coveragePatientPayload([
            'first_name' => $xss,
            'patient_number' => 'HACK-001',
            'id' => 999999,
            'status' => 'archived',
            'role' => ClinicRole::Admin->value,
        ]))
        ->assertRedirect();

    $patient = Patient::query()->first();

    expect($patient)->not->toBeNull()
        ->and($patient->first_name)->toBe($xss)
        ->and($patient->patient_number)->toMatch('/^PAT-\d{4}-\d{5}$/')
        ->and($patient->patient_number)->not->toBe('HACK-001')
        ->and($patient->id)->not->toBe(999999);
});

test('patient create rejects overlong names invalid gender and sql-like dates', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('patients.store'), coveragePatientPayload([
            'first_name' => str_repeat('A', 256),
            'gender' => 'not-a-gender',
            'date_of_birth' => "' OR 1=1 --",
            'email' => 'not-an-email',
        ]))
        ->assertInvalid(['first_name', 'gender', 'date_of_birth', 'email']);

    expect(Patient::query()->count())->toBe(0);
});

test('search query strings do not 500', function (string $route, array $query) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route($route, $query))
        ->assertOk();
})->with([
    'patients sql' => ['patients.index', ['search' => "1' OR '1'='1"]],
    'patients xss' => ['patients.index', ['search' => '<script>alert(1)</script>']],
    'inventory sql' => ['inventory.index', ['search' => "1' OR '1'='1"]],
    'suppliers sql' => ['inventory.suppliers.index', ['search' => "1' OR '1'='1"]],
    'purchase orders sql' => ['inventory.purchase-orders.index', ['search' => "1' OR '1'='1"]],
    'lab sql' => ['lab.index', ['search' => "1' OR '1'='1"]],
    'imaging sql' => ['imaging.index', ['search' => "1' OR '1'='1"]],
    'treatments sql' => ['treatments.index', ['search' => "1' OR '1'='1"]],
    'billing sql' => ['billing.index', ['search' => "1' OR '1'='1"]],
    'reports garbage dates' => ['reports.payments', ['from' => "1' OR 1=1", 'to' => '<script>']],
    'reports swapped dates' => ['reports.payments', ['from' => '2026-12-31', 'to' => '2026-01-01']],
]);

test('patient json search with abuse query does not 500', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->getJson(route('patients.search', ['q' => "1' OR '1'='1"]))
        ->assertOk()
        ->assertJson(['patients' => []]);
});

test('appointments reject nonexistent ids and extra privileged keys', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('appointments.store'), [
            'patient_id' => 999999,
            'dentist_id' => 999999,
            'chair_id' => 999999,
            'starts_at' => 'not-a-date',
            'number' => 'APT-HACK-00001',
        ])
        ->assertInvalid(['patient_id', 'dentist_id', 'chair_id', 'starts_at']);
});

test('treatments store xss diagnosis and ignore forged status when valid otherwise', function () {
    $admin = User::factory()->admin()->create();
    $patient = Patient::factory()->create();
    $dentist = Dentist::factory()->create();
    $feeItem = FeeItem::factory()->create();
    $xss = '<img src=x onerror=alert(1)>';

    $this->actingAs($admin)
        ->post(route('treatments.store'), [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'diagnosis' => $xss,
            'status' => TreatmentStatus::Cancelled->value,
            'id' => 999999,
            'procedures' => [
                ['fee_item_id' => $feeItem->id, 'quantity' => 1],
            ],
            'prescription_items' => [
                ['medication' => $xss, 'dosage' => '1mg', 'instructions' => 'none'],
            ],
        ])
        ->assertRedirect();

    $treatment = Treatment::query()->first();

    expect($treatment)->not->toBeNull()
        ->and($treatment->diagnosis)->toBe($xss)
        ->and($treatment->id)->not->toBe(999999);
});

test('payments reject negative amounts invalid methods and ignore amount_cents', function () {
    $admin = User::factory()->admin()->create();
    $invoice = Invoice::factory()->create(['balance_cents' => 5000]);

    $this->actingAs($admin)
        ->post(route('billing.payments.store', $invoice), [
            'amount' => '-10',
            'method' => 'bitcoin',
            'amount_cents' => 1,
        ])
        ->assertInvalid(['amount', 'method']);

    expect(Payment::query()->count())->toBe(0);

    $this->actingAs($admin)
        ->post(route('billing.payments.store', $invoice), [
            'amount' => '10.00',
            'method' => PaymentMethod::Cash->value,
            'amount_cents' => 1,
        ])
        ->assertRedirect();

    expect(Payment::query()->first()->amount_cents)->toBe(1000);
});

test('expenses reject extra decimals and store xss description', function () {
    $admin = User::factory()->admin()->create();
    $xss = '<script>alert(1)</script>';

    $this->actingAs($admin)
        ->post(route('expenses.store'), [
            'description' => 'Paper',
            'category' => 'supplies',
            'amount' => '10.001',
            'expense_date' => now()->toDateString(),
        ])
        ->assertInvalid(['amount']);

    $this->actingAs($admin)
        ->post(route('expenses.store'), [
            'description' => $xss,
            'category' => 'supplies',
            'amount' => '12.50',
            'expense_date' => now()->toDateString(),
            'amount_cents' => 1,
        ])
        ->assertRedirect();

    expect(Expense::query()->first()->description)->toBe($xss)
        ->and(Expense::query()->first()->amount_cents)->toBe(1250);
});

test('lab imaging and odontogram reject invalid enums files and cross-patient encounters', function () {
    $admin = User::factory()->admin()->create();
    $records = ClinicSurface::records();
    $otherEncounter = Encounter::factory()->create();

    $this->actingAs($admin)
        ->post(route('lab.store'), [
            'patient_id' => $records['patient']->id,
            'dentist_id' => $records['dentist']->id,
            'description' => str_repeat('L', 256),
        ])
        ->assertInvalid(['description']);

    $this->actingAs($admin)
        ->post(route('lab.transition', $records['labOrder']), [
            'status' => LabOrderStatus::Fitted->value,
        ])
        ->assertInvalid(['status']);

    $this->actingAs($admin)
        ->post(route('imaging.store'), [
            'patient_id' => $records['patient']->id,
            'dentist_id' => $records['dentist']->id,
            'type' => ImagingOrderType::Bitewing->value,
            'file' => UploadedFile::fake()->create('malware.exe', 20),
        ])
        ->assertInvalid(['file']);

    $this->actingAs($admin)
        ->patch(route('patients.odontogram.update', $records['patient']), [
            'tooth_fdi' => '99',
            'status' => 'exploded',
            'encounter_id' => $otherEncounter->id,
        ])
        ->assertInvalid(['tooth_fdi', 'status', 'encounter_id']);
});

test('inventory rejects negative quantities and ignores forged unit cost cents', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('inventory.store'), [
            'name' => 'Gloves',
            'category' => InventoryCategory::Ppe->value,
            'quantity' => -3,
            'unit' => 'box',
            'reorder_level' => -1,
            'unit_cost' => 'abc',
        ])
        ->assertInvalid(['quantity', 'reorder_level', 'unit_cost']);

    $this->actingAs($admin)
        ->post(route('inventory.store'), [
            'name' => 'Gloves',
            'category' => InventoryCategory::Ppe->value,
            'quantity' => 0,
            'unit' => 'box',
            'reorder_level' => 2,
            'unit_cost' => '12.50',
            'unit_cost_cents' => 1,
        ])
        ->assertRedirect();

    expect(InventoryItem::query()->first()->unit_cost_cents)->toBe(1250);
});

test('purchase orders ignore forged numbers and dentist cannot receive', function () {
    $admin = User::factory()->admin()->create();
    $supplier = Supplier::factory()->create();
    $item = InventoryItem::factory()->create();

    $this->actingAs($admin)
        ->post(route('inventory.purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'number' => 'PO-HACK-00001',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity_ordered' => 2,
                    'unit_cost' => '3.00',
                    'expiry_date' => now()->addMonth()->toDateString(),
                ],
            ],
        ])
        ->assertRedirect();

    $order = PurchaseOrder::query()->first();

    expect($order->number)->toMatch('/^PO-\d{4}-\d{5}$/')
        ->and($order->number)->not->toBe('PO-HACK-00001');

    $dentist = User::factory()->dentist()->create();

    $this->actingAs($dentist)
        ->post(route('inventory.purchase-orders.receive', $order))
        ->assertForbidden();
});

test('staff store rejects unknown roles and extra admin flags', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('staff.store'), [
            'name' => 'Hacker',
            'email' => 'hacker@goldensmile.clinic',
            'role' => 'superadmin',
            'password' => 'password12',
            'password_confirmation' => 'password12',
            'is_admin' => true,
        ])
        ->assertInvalid(['role']);

    expect(User::query()->where('email', 'hacker@goldensmile.clinic')->exists())->toBeFalse();
});

test('soap overlong body is rejected and xss amendment is stored as text', function () {
    $admin = User::factory()->admin()->create();
    $encounter = ClinicSurface::unsignedEncounter();
    $xss = '<script>alert(1)</script>';

    $this->actingAs($admin)
        ->patch(route('encounters.soap.update', $encounter), [
            'subjective' => str_repeat('S', 65536),
        ])
        ->assertInvalid(['subjective']);

    $signed = ClinicSurface::unsignedEncounter();
    $this->actingAs($admin)->post(route('encounters.sign', $signed))->assertRedirect();

    $this->actingAs($admin)
        ->post(route('encounters.amendments.store', $signed), [
            'body' => $xss,
        ])
        ->assertRedirect();

    expect($signed->fresh('soapNote.amendments')->soapNote->amendments->first()->body)->toBe($xss);
});

test('insurance claim xss provider is stored and invalid status is rejected', function () {
    $admin = User::factory()->admin()->create();
    $invoice = Invoice::factory()->create();
    $xss = '<script>alert(1)</script>';

    $this->actingAs($admin)
        ->post(route('billing.insurance-claims.store', $invoice), [
            'provider' => $xss,
            'status' => 'bogus',
        ])
        ->assertInvalid(['status']);

    $this->actingAs($admin)
        ->post(route('billing.insurance-claims.store', $invoice), [
            'provider' => $xss,
            'status' => InsuranceClaimStatus::Draft->value,
        ])
        ->assertRedirect();

    expect(InsuranceClaim::query()->where('invoice_id', $invoice->id)->first()?->provider)->toBe($xss);
});

test('notification template overlong body is rejected', function () {
    $admin = User::factory()->admin()->create();
    $template = CommunicationTemplate::factory()->create([
        'code' => 'ABU-'.fake()->unique()->numerify('#####'),
    ]);

    $this->actingAs($admin)
        ->patch(route('notification-templates.update', $template), [
            'body' => str_repeat('B', 2001),
        ])
        ->assertInvalid(['body']);
});

test('plan item acceptance rejects unknown values', function () {
    $admin = User::factory()->admin()->create();
    $plan = TreatmentPlan::factory()->create();
    $item = $plan->items()->create([
        'description' => 'Crown',
        'fee_cents' => 1000,
        'acceptance_status' => TreatmentPlanItemAcceptance::Proposed,
    ]);

    $this->actingAs($admin)
        ->patch(route('treatment-plans.items.update', [$plan, $item]), [
            'acceptance_status' => 'maybe',
        ])
        ->assertInvalid(['acceptance_status']);
});

test('cash close and mm recon reject negative money and unknown providers', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('expenses.daily-closings.store'), [
            'closing_date' => now()->toDateString(),
            'counted_cash' => '-1',
        ])
        ->assertInvalid(['counted_cash']);

    $this->actingAs($admin)
        ->post(route('expenses.mobile-money-reconciliations.store'), [
            'reconciliation_date' => now()->toDateString(),
            'provider' => 'WesternUnion',
            'provider_total' => '10.00',
        ])
        ->assertInvalid(['provider']);

    expect(MobileMoneyProvider::Telesom->value)->toBe('Telesom');
});

test('inventory adjust as nurse is forbidden', function () {
    $nurse = User::factory()->nurse()->create();
    $item = InventoryItem::factory()->create();

    $this->actingAs($nurse)
        ->post(route('inventory.adjust', $item), [
            'type' => InventoryMovementType::AdjustmentIn->value,
            'quantity' => 1,
            'expiry_date' => now()->addMonth()->toDateString(),
        ])
        ->assertForbidden();
});
