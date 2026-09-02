<?php

use App\Enums\ClinicRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('admin can create staff of any role', function (ClinicRole $role) {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('staff.store'), [
        'name' => 'New Staff',
        'email' => "staff-{$role->value}@example.com",
        'role' => $role->value,
        'password' => 'password12',
        'password_confirmation' => 'password12',
    ]);

    $response->assertRedirectToRoute('staff.index');

    $staff = User::query()->where('email', "staff-{$role->value}@example.com")->first();

    expect($staff)->not->toBeNull();
    expect($staff->role)->toBe($role);
    expect(Hash::check('password12', $staff->password))->toBeTrue();
})->with([
    'admin' => ClinicRole::Admin,
    'dentist' => ClinicRole::Dentist,
    'receptionist' => ClinicRole::Receptionist,
    'nurse' => ClinicRole::Nurse,
    'accountant' => ClinicRole::Accountant,
    'lab' => ClinicRole::Lab,
]);

test('creating staff does not log the admin out', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('staff.store'), [
        'name' => 'New Staff',
        'email' => 'newstaff@example.com',
        'role' => ClinicRole::Receptionist->value,
        'password' => 'password12',
        'password_confirmation' => 'password12',
    ]);

    $this->assertAuthenticatedAs($admin);
});

test('non-admin roles cannot view staff index', function (ClinicRole $role) {
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)
        ->get(route('staff.index'))
        ->assertForbidden();
})->with([
    'dentist' => ClinicRole::Dentist,
    'receptionist' => ClinicRole::Receptionist,
    'nurse' => ClinicRole::Nurse,
    'accountant' => ClinicRole::Accountant,
    'lab' => ClinicRole::Lab,
]);

test('non-admin roles cannot access staff create form', function (ClinicRole $role) {
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)
        ->get(route('staff.create'))
        ->assertForbidden();
})->with([
    'dentist' => ClinicRole::Dentist,
    'receptionist' => ClinicRole::Receptionist,
    'nurse' => ClinicRole::Nurse,
    'accountant' => ClinicRole::Accountant,
    'lab' => ClinicRole::Lab,
]);

test('non-admin roles cannot create staff', function (ClinicRole $role) {
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)->post(route('staff.store'), [
        'name' => 'Blocked Staff',
        'email' => "blocked-{$role->value}@example.com",
        'role' => ClinicRole::Nurse->value,
        'password' => 'password12',
        'password_confirmation' => 'password12',
    ])->assertForbidden();
})->with([
    'dentist' => ClinicRole::Dentist,
    'receptionist' => ClinicRole::Receptionist,
    'nurse' => ClinicRole::Nurse,
    'accountant' => ClinicRole::Accountant,
    'lab' => ClinicRole::Lab,
]);

test('staff password shorter than ten characters is rejected', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->from(route('staff.index'))->post(route('staff.store'), [
        'name' => 'Short Password',
        'email' => 'short@example.com',
        'role' => ClinicRole::Receptionist->value,
        'password' => 'short9ch',
        'password_confirmation' => 'short9ch',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertDatabaseMissing('users', ['email' => 'short@example.com']);
});
