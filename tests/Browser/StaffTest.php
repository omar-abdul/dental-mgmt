<?php

use App\Enums\ClinicRole;
use App\Models\User;

test('admin can create a staff member from settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $page = visit(route('staff.index'));

    $page->assertSee('Staff')
        ->fill('name', 'New Nurse')
        ->fill('email', 'nurse2@goldensmile.clinic')
        ->select('role', ClinicRole::Nurse->value)
        ->fill('password', 'password12')
        ->fill('password_confirmation', 'password12')
        ->click('@create-staff-button')
        ->assertSee('New Nurse')
        ->assertSee('nurse2@goldensmile.clinic')
        ->assertNoJavaScriptErrors();

    $staff = User::query()->where('email', 'nurse2@goldensmile.clinic')->first();

    expect($staff)->not->toBeNull()
        ->and($staff->role)->toBe(ClinicRole::Nurse)
        ->and($staff->name)->toBe('New Nurse');
});
