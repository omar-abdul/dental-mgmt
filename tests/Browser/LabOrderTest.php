<?php

use App\Enums\LabOrderStatus;
use App\Models\LabOrder;
use App\Models\User;

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
