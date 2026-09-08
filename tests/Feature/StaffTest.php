<?php

use App\Enums\ClinicRole;
use App\Models\Dentist;
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

    if ($role === ClinicRole::Dentist) {
        expect($staff->dentist)->not->toBeNull()
            ->and($staff->dentist->display_name)->toBe('New Staff')
            ->and($staff->dentist->is_active)->toBeTrue()
            ->and($staff->dentist->created_by)->toBe($admin->id);
    } else {
        expect($staff->dentist)->toBeNull();
    }
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

test('new staff users default to active', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('staff.store'), [
        'name' => 'Active Staff',
        'email' => 'active@example.com',
        'role' => ClinicRole::Nurse->value,
        'password' => 'password12',
        'password_confirmation' => 'password12',
    ])->assertRedirectToRoute('staff.index');

    expect(User::query()->where('email', 'active@example.com')->value('is_active'))->toBeTrue();
});

test('admin can deactivate and reactivate staff', function () {
    $admin = User::factory()->admin()->create();
    $nurse = User::factory()->nurse()->create(['is_active' => true]);

    $this->actingAs($admin)
        ->patch(route('staff.update', $nurse), ['is_active' => false])
        ->assertRedirectToRoute('staff.index');

    expect($nurse->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->patch(route('staff.update', $nurse), ['is_active' => true])
        ->assertRedirectToRoute('staff.index');

    expect($nurse->fresh()->is_active)->toBeTrue();
});

test('admin cannot deactivate their own account', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('staff.index'))
        ->patch(route('staff.update', $admin), ['is_active' => false])
        ->assertSessionHasErrors('is_active');

    expect($admin->fresh()->is_active)->toBeTrue();
});

test('deactivating a dentist syncs dentist profile is_active', function () {
    $admin = User::factory()->admin()->create();
    $dentistUser = User::factory()->dentist()->create();
    $dentist = Dentist::factory()->forUser($dentistUser)->create(['is_active' => true]);

    $this->actingAs($admin)
        ->patch(route('staff.update', $dentistUser), ['is_active' => false])
        ->assertRedirectToRoute('staff.index');

    expect($dentistUser->fresh()->is_active)->toBeFalse();
    expect($dentist->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->get(route('treatments.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('dentists', 0));
});

test('non-admin roles cannot update staff active status', function (ClinicRole $role) {
    $user = User::factory()->role($role)->create();
    $target = User::factory()->nurse()->create();

    $this->actingAs($user)
        ->patch(route('staff.update', $target), ['is_active' => false])
        ->assertForbidden();
})->with([
    'dentist' => ClinicRole::Dentist,
    'receptionist' => ClinicRole::Receptionist,
    'nurse' => ClinicRole::Nurse,
    'accountant' => ClinicRole::Accountant,
    'lab' => ClinicRole::Lab,
]);
