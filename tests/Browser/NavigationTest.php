<?php

use App\Models\User;

test('accountant sidebar hides clinical modules they cannot use', function () {
    $accountant = User::factory()->accountant()->create();

    $this->actingAs($accountant);

    $page = visit(route('dashboard'));

    $page->assertSee('Dashboard')
        ->assertSee('Billing')
        ->assertSee('Expenses')
        ->assertSee('Reports')
        ->assertDontSee('Patients')
        ->assertDontSee('Appointments')
        ->assertDontSee('Treatments')
        ->assertDontSee('Inventory')
        ->assertNoJavaScriptErrors();
});

test('admin sidebar includes chart module', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    visit(route('dashboard'))
        ->assertSee('Chart')
        ->assertSee('Lab')
        ->assertSee('Imaging')
        ->assertNoJavaScriptErrors();
});

test('receptionist sidebar hides lab and imaging modules', function () {
    $receptionist = User::factory()->receptionist()->create();

    $this->actingAs($receptionist);

    visit(route('dashboard'))
        ->assertDontSee('Lab')
        ->assertDontSee('Imaging')
        ->assertNoJavaScriptErrors();
});

test('dentist sidebar hides expenses module', function () {
    $dentist = User::factory()->dentist()->create();

    $this->actingAs($dentist);

    visit(route('dashboard'))
        ->assertSee('Billing')
        ->assertDontSee('Expenses')
        ->assertNoJavaScriptErrors();
});

test('admin can open the reports hub', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $page = visit(route('reports.index'));

    $page->assertSee('Reports')
        ->assertSee('Daily appointments')
        ->assertSee('Completed payments')
        ->assertDontSee('This module is coming soon in a future release.')
        ->assertNoJavaScriptErrors();
});
