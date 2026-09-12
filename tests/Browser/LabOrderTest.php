<?php

use App\Enums\LabOrderStatus;
use App\Models\Dentist;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\User;

test('lab staff can search and select a patient when creating an order', function () {
    $labUser = User::factory()->lab()->create();
    $dentist = Dentist::factory()->create([
        'display_name' => 'Dr. Amina Yusuf',
    ]);
    $patient = Patient::factory()->create([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'patient_number' => 'PAT-2026-00042',
        'phone' => '+252611110000',
    ]);

    $this->actingAs($labUser);

    $page = visit(route('lab.create'));

    $page->assertSee('New lab order')
        ->typeSlowly('@patient-picker-search', 'Maria', 80)
        ->assertQueryStringMissing('patient_id')
        ->assertSee('Maria Santos')
        ->click('@patient-picker-option')
        ->assertSee('Maria Santos (PAT-2026-00042)')
        ->select('dentist_id', (string) $dentist->id)
        ->fill('@lab-order-description-input', 'Zirconia crown #36')
        ->click('@create-lab-order-button')
        ->assertSee('LAB-')
        ->assertSee('Zirconia crown #36')
        ->assertNoJavaScriptErrors();

    $order = LabOrder::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->patient_id)->toBe($patient->id)
        ->and($order->dentist_id)->toBe($dentist->id)
        ->and($order->description)->toBe('Zirconia crown #36');
});

test('lab staff can open a lab order and advance it through statuses', function () {
    $labUser = User::factory()->lab()->create();
    $order = LabOrder::factory()->create([
        'description' => 'PFM bridge 14-16',
        'status' => LabOrderStatus::Ordered,
    ]);

    $this->actingAs($labUser);

    $page = visit(route('lab.show', $order));

    $page->assertSee($order->number)
        ->assertSee('PFM bridge 14-16')
        ->assertSee('Ordered')
        ->assertNoJavaScriptErrors();

    $page->click('@lab-transition-received_by_lab-button')
        ->assertSee('Received by lab')
        ->assertNoJavaScriptErrors();

    expect($order->fresh()->status)->toBe(LabOrderStatus::ReceivedByLab);

    $page->click('@lab-transition-in_production-button')
        ->assertSee('In production')
        ->assertNoJavaScriptErrors();

    expect($order->fresh()->status)->toBe(LabOrderStatus::InProduction);

    $page->click('@lab-transition-ready-button')
        ->assertSee('Ready')
        ->assertNoJavaScriptErrors();

    expect($order->fresh()->status)->toBe(LabOrderStatus::Ready);
});

test('receptionist cannot see lab in sidebar or open lab module', function () {
    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist);

    visit(route('dashboard'))
        ->assertDontSee('Lab')
        ->assertNoJavaScriptErrors();

    $this->actingAs($receptionist)
        ->get(route('lab.index'))
        ->assertForbidden();
});

test('lab staff sidebar includes lab module', function () {
    $labUser = User::factory()->lab()->create();

    $this->actingAs($labUser);

    visit(route('dashboard'))
        ->assertSee('Lab')
        ->assertNoJavaScriptErrors();
});
