<?php

use Database\Seeders\FeeItemSeeder;
use Database\Seeders\GoldenSmileNamedSeeder;
use Database\Seeders\WorkingHourSeeder;

test('demo admin can log in after named seed and sees overview content', function () {
    $this->seed([
        FeeItemSeeder::class,
        WorkingHourSeeder::class,
        GoldenSmileNamedSeeder::class,
    ]);

    $page = visit(route('login'));

    $page->fill('email', 'a.santos@goldensmile.clinic')
        ->fill('password', 'password12')
        ->click('@login-button')
        ->assertSee('Overview')
        ->assertSee('Dr. A. Santos')
        ->assertSee('Maria Santos')
        ->assertNoJavaScriptErrors();

    $this->assertAuthenticated();
});
