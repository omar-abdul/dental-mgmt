<?php

use App\Enums\ImagingOrderType;
use App\Models\Dentist;
use App\Models\ImagingOrder;
use App\Models\Patient;
use App\Models\User;

test('dentist can create an imaging order with optional file from the UI', function () {
    $dentistUser = User::factory()->dentist()->create(['name' => 'Dr. R. Lim']);
    $dentist = Dentist::factory()->forUser($dentistUser)->create([
        'display_name' => 'Dr. R. Lim',
    ]);
    $patient = Patient::factory()->create([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'patient_number' => 'PAT-2026-00042',
        'phone' => '+252611110000',
    ]);

    $this->actingAs($dentistUser);

    $page = visit(route('imaging.create'));

    $page->assertSee('New imaging order')
        ->assertSee('Attachment (optional)')
        ->fill('@patient-picker-search', 'Maria')
        ->click('@patient-picker-option')
        ->assertSee('Maria Santos (PAT-2026-00042)')
        ->select('dentist_id', (string) $dentist->id)
        ->select('@imaging-order-type-select', ImagingOrderType::Bitewing->value)
        ->fill('notes', 'Lower left bitewing series')
        ->fill('result_findings', 'No caries detected.')
        ->fill('result_impression', 'Normal bitewing radiograph.')
        ->click('@create-imaging-order-button')
        ->assertSee('IMG-')
        ->assertSee('Bitewing')
        ->assertSee('No caries detected.')
        ->assertNoJavaScriptErrors();

    $order = ImagingOrder::query()->with(['result', 'files'])->first();

    expect($order)->not->toBeNull()
        ->and($order->patient_id)->toBe($patient->id)
        ->and($order->dentist_id)->toBe($dentist->id)
        ->and($order->type)->toBe(ImagingOrderType::Bitewing)
        ->and($order->notes)->toBe('Lower left bitewing series')
        ->and($order->result)->not->toBeNull()
        ->and($order->result->findings)->toBe('No caries detected.')
        ->and($order->files)->toHaveCount(0);
});

test('receptionist cannot see imaging in sidebar or open imaging module', function () {
    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist);

    visit(route('dashboard'))
        ->assertDontSee('Imaging')
        ->assertNoJavaScriptErrors();

    $this->actingAs($receptionist)
        ->get(route('imaging.index'))
        ->assertForbidden();
});

test('admin sidebar includes imaging module', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    visit(route('dashboard'))
        ->assertSee('Imaging')
        ->assertNoJavaScriptErrors();
});
