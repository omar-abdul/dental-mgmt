<?php

use App\Enums\ClinicRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('login rejects an empty payload', function () {
    $this->from(route('login'))
        ->post(route('login.store'), [])
        ->assertSessionHasErrors();

    $this->assertGuest();
});

test('login rejects sql-like and oversized identifiers without authenticating', function (string $email, string $password) {
    User::factory()->create([
        'email' => 'staff@goldensmile.clinic',
        'password' => Hash::make('password12'),
    ]);

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => $email,
            'password' => $password,
        ]);

    $this->assertGuest();
})->with([
    'sql email' => ["' OR '1'='1", 'password12'],
    'xss email' => ['<script>alert(1)</script>@x.test', 'password12'],
    'overlong password' => ['staff@goldensmile.clinic', str_repeat('a', 5000)],
    'wrong password' => ['staff@goldensmile.clinic', 'not-the-password'],
]);

test('forgot password rejects invalid emails', function (array $payload) {
    $this->from(route('password.request'))
        ->post(route('password.email'), $payload)
        ->assertSessionHasErrors('email');
})->with([
    'empty' => [[]],
    'not an email' => [['email' => 'not-an-email']],
    'xss' => [['email' => '<script>alert(1)</script>']],
    'sql' => [['email' => "' OR 1=1 --@x.test"]],
]);

test('password reset rejects missing fields and overlong passwords', function () {
    $this->from(route('password.request'))
        ->post(route('password.update'), [])
        ->assertSessionHasErrors();

    $user = User::factory()->create();

    $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => str_repeat('a', 5000),
        'password_confirmation' => str_repeat('a', 5000),
    ])->assertSessionHasErrors();

    expect(Hash::check('password12', $user->fresh()->password))->toBeTrue();
});

test('password confirmation rejects a wrong password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('password.confirm'))
        ->post(route('password.confirm.store'), [
            'password' => 'wrong-password',
        ])
        ->assertSessionHasErrors('password');
});

test('staff cannot escalate role through profile update extra keys', function () {
    $user = User::factory()->receptionist()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Still Reception',
            'email' => $user->email,
            'role' => ClinicRole::Admin->value,
            'is_admin' => true,
        ])
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh()->role)->toBe(ClinicRole::Receptionist)
        ->and($user->fresh()->name)->toBe('Still Reception');
});

test('profile update rejects xss-invalid email and overlong name', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => str_repeat('N', 256),
            'email' => '<script>alert(1)</script>',
        ])
        ->assertInvalid(['name', 'email']);
});

test('password update rejects mismatched confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'password12',
            'password' => 'brand-new-password12',
            'password_confirmation' => 'different-password12',
        ])
        ->assertSessionHasErrors('password');
});

test('forgot password does not enumerate missing users via a 500', function () {
    Notification::fake();

    $this->from(route('password.request'))
        ->post(route('password.email'), [
            'email' => 'nobody@goldensmile.clinic',
        ])
        ->assertRedirect();
});
