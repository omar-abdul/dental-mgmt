<?php

use App\Enums\ClinicRole;
use App\Models\User;
use Database\Seeders\WorkingHourSeeder;
use Illuminate\Support\Carbon;

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

test('staff-created dentist appears on the appointments calendar and book form', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-02 09:00:00', 'Africa/Mogadishu'));
    $this->seed(WorkingHourSeeder::class);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    visit(route('staff.index'))
        ->assertSee('Staff')
        ->fill('name', 'Dr. Amina Yusuf')
        ->fill('email', 'a.yusuf@goldensmile.clinic')
        ->select('role', ClinicRole::Dentist->value)
        ->fill('password', 'password12')
        ->fill('password_confirmation', 'password12')
        ->click('@create-staff-button')
        ->assertSee('Dr. Amina Yusuf')
        ->assertNoJavaScriptErrors();

    visit(route('appointments.index', ['date' => '2026-09-02']))
        ->assertSee('Dr. Amina Yusuf')
        ->click('@book-appointment-button')
        ->assertSee('Book appointment')
        ->assertSee('Dr. Amina Yusuf')
        ->assertNoJavaScriptErrors();

    Carbon::setTestNow();
});
