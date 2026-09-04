<?php

use App\Models\User;

test('authenticated staff see overview kpis activity and upcoming empty states', function () {
    $user = User::factory()->admin()->create(['name' => 'Dr. A. Santos']);

    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->assertSee('Overview')
        ->assertSee('Dr. A. Santos')
        ->assertSee("Today's appointments")
        ->assertSee('Active patients')
        ->assertSee('Unpaid invoices')
        ->assertSee('Low-stock items')
        ->assertSee('Weekly visits')
        ->assertSee('Recent activity')
        ->assertSee('No recent activity yet.')
        ->assertSee('Upcoming today')
        ->assertSee('No upcoming appointments for the rest of today.')
        ->assertNoJavaScriptErrors();
});
