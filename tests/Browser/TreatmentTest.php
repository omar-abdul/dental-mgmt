<?php

use App\Enums\TreatmentStatus;
use App\Models\Dentist;
use App\Models\FeeItem;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Treatment;
use App\Models\User;

test('dentist can record a treatment and prescription from the form', function () {
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
    $feeItem = FeeItem::factory()->create([
        'name' => 'Composite filling',
        'price_cents' => 5000,
    ]);

    $this->actingAs($dentistUser);

    $page = visit(route('treatments.index'));

    $page->assertSee('No treatments found.')
        ->click('@record-treatment-link')
        ->assertSee('Record treatment')
        ->fill('@patient-picker-search', 'Maria')
        ->click('@patient-picker-option')
        ->assertSee('Maria Santos (PAT-2026-00042)')
        ->select('dentist_id', (string) $dentist->id)
        ->fill('diagnosis', 'Dental caries on tooth 36')
        ->select('#fee_item_id_1', (string) $feeItem->id)
        ->fill('#quantity_1', '1')
        ->fill('#medication_1', 'Amoxicillin')
        ->fill('#dosage_1', '500mg')
        ->fill('#instructions_1', 'Take one capsule three times daily for 5 days.')
        ->click('@save-treatment-button')
        ->assertSee('Dental caries on tooth 36')
        ->assertSee('Maria Santos')
        ->assertNoJavaScriptErrors();

    $treatment = Treatment::query()->first();
    $prescription = Prescription::query()->first();

    expect($treatment)->not->toBeNull()
        ->and($treatment->diagnosis)->toBe('Dental caries on tooth 36')
        ->and($treatment->patient_id)->toBe($patient->id)
        ->and($treatment->dentist_id)->toBe($dentist->id)
        ->and($prescription)->not->toBeNull()
        ->and($prescription->prescriber_id)->toBe($dentistUser->id)
        ->and($prescription->number)->toMatch('/^RX-\d{4}-\d{5}$/');
});

test('dentist can mark a planned treatment completed from the show page', function () {
    $dentistUser = User::factory()->dentist()->create();
    $dentist = Dentist::factory()->forUser($dentistUser)->create();
    $treatment = Treatment::factory()->create([
        'dentist_id' => $dentist->id,
        'status' => TreatmentStatus::Planned,
        'diagnosis' => 'Gingivitis',
    ]);

    $this->actingAs($dentistUser);

    $page = visit(route('treatments.show', $treatment));

    $page->assertSee('Gingivitis')
        ->click('@complete-treatment-button')
        ->assertSee('Completed')
        ->assertNoJavaScriptErrors();

    expect($treatment->fresh()->status)->toBe(TreatmentStatus::Completed);
});
