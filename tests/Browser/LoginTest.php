<?php

use App\Models\User;

test('staff can log in from the split login page and reach the dashboard', function () {
    $user = User::factory()->receptionist()->create([
        'name' => 'Front Desk',
        'email' => 'frontdesk@goldensmile.clinic',
    ]);

    $page = visit(route('login'));

    $page->assertSee('Username or Email')
        ->assertSee('Admin · Dentist · Receptionist · Nurse · Accountant · Lab')
        ->assertDontSee('Sign up')
        ->fill('email', $user->email)
        ->fill('password', 'password12')
        ->click('@login-button')
        ->assertSee('Overview')
        ->assertSee('Front Desk')
        ->assertNoJavaScriptErrors();

    $this->assertAuthenticatedAs($user);
});

test('login with an invalid password stays on the login page', function () {
    $user = User::factory()->receptionist()->create();

    $page = visit(route('login'));

    $page->fill('email', $user->email)
        ->fill('password', 'wrong-password')
        ->click('@login-button')
        ->assertSee('These credentials do not match our records.')
        ->assertSee('Log in');

    $this->assertGuest();
});
