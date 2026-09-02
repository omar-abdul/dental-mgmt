<?php

use App\Enums\ClinicRole;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/Login')
        ->missing('teamInvitation')
    );
});

test('login screen does not include registration link', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertDontSee('Sign up', false);
    $response->assertDontSee('register-link', false);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password12',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirectToRoute('dashboard');
});

test('each clinic role can authenticate via login screen', function (ClinicRole $role) {
    $user = User::factory()->role($role)->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password12',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirectToRoute('dashboard');
})->with([
    'admin' => ClinicRole::Admin,
    'dentist' => ClinicRole::Dentist,
    'receptionist' => ClinicRole::Receptionist,
    'nurse' => ClinicRole::Nurse,
    'accountant' => ClinicRole::Accountant,
    'lab' => ClinicRole::Lab,
]);

test('users can authenticate with remember me', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password12',
        'remember' => true,
    ]);

    $response->assertRedirectToRoute('dashboard');
    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect(route('home'));
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});
