<?php

use App\Enums\AppointmentStatus;
use App\Enums\ClinicRole;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\CommunicationTemplate;
use App\Models\MobileMoneyTransaction;
use App\Models\Payment;
use App\Models\User;
use App\Services\AppointmentReminderService;
use Database\Seeders\CommunicationTemplateSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

test('communication template seeder inserts dcms templates', function () {
    $this->seed(CommunicationTemplateSeeder::class);

    expect(CommunicationTemplate::query()->count())->toBe(3);

    $reminder = CommunicationTemplate::query()->find('APT-REMINDER');
    expect($reminder)->not->toBeNull();
    expect($reminder->channel)->toBe('SMS');
    expect($reminder->body)->toContain('{patient_name}');
});

test('admin can view notification templates page', function () {
    $this->seed(CommunicationTemplateSeeder::class);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('notification-templates.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/NotificationTemplates')
            ->has('templates', 3));
});

test('admin can update a notification template body', function () {
    $this->seed(CommunicationTemplateSeeder::class);
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->patch(
        route('notification-templates.update', 'APT-REMINDER'),
        ['body' => 'Updated reminder for {patient_name} on {date} at {time}.'],
    );

    $response->assertRedirectToRoute('notification-templates.index');

    $this->assertDatabaseHas('communication_templates', [
        'code' => 'APT-REMINDER',
        'body' => 'Updated reminder for {patient_name} on {date} at {time}.',
    ]);
});

test('non-admin roles cannot view or update notification templates', function (ClinicRole $role) {
    $this->seed(CommunicationTemplateSeeder::class);
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)
        ->get(route('notification-templates.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('notification-templates.update', 'APT-REMINDER'), [
            'body' => 'Blocked update.',
        ])
        ->assertForbidden();
})->with([
    'dentist' => ClinicRole::Dentist,
    'receptionist' => ClinicRole::Receptionist,
    'nurse' => ClinicRole::Nurse,
    'accountant' => ClinicRole::Accountant,
    'lab' => ClinicRole::Lab,
]);

test('appointment reminder command writes would-send audit without outbound http', function () {
    Http::fake();
    $this->seed(CommunicationTemplateSeeder::class);

    $now = now()->startOfHour();
    $this->travelTo($now);

    $appointment = Appointment::factory()->create([
        'starts_at' => $now->copy()->addHours(24),
        'ends_at' => $now->copy()->addHours(24)->addMinutes(30),
        'status' => AppointmentStatus::Confirmed,
    ]);

    $this->artisan('appointments:queue-reminders')->assertSuccessful();

    $this->assertDatabaseHas('audit_logs', [
        'action' => AppointmentReminderService::WOULD_SEND_ACTION,
        'auditable_type' => Appointment::class,
        'auditable_id' => $appointment->id,
    ]);

    $audit = AuditLog::query()->first();
    expect($audit->meta['reminder_hours'])->toBe(24)
        ->and($audit->meta['template_code'])->toBe('APT-REMINDER')
        ->and($audit->meta['channel'])->toBe('SMS')
        ->and($audit->meta['body'])->toContain($appointment->patient->first_name);

    Http::assertNothingSent();

    $this->artisan('appointments:queue-reminders')->assertSuccessful();

    expect(AuditLog::query()->where('action', AppointmentReminderService::WOULD_SEND_ACTION)->count())->toBe(1);
});

test('appointment reminder command queues 48 24 and 2 hour horizons', function () {
    Http::fake();
    $this->seed(CommunicationTemplateSeeder::class);

    $now = now()->startOfHour();
    $this->travelTo($now);

    foreach ([48, 24, 2] as $hours) {
        Appointment::factory()->create([
            'starts_at' => $now->copy()->addHours($hours),
            'ends_at' => $now->copy()->addHours($hours)->addMinutes(30),
            'status' => AppointmentStatus::Scheduled,
        ]);
    }

    $this->artisan('appointments:queue-reminders')->assertSuccessful();

    expect(AuditLog::query()->where('action', AppointmentReminderService::WOULD_SEND_ACTION)->count())->toBe(3);
    expect(AuditLog::query()->pluck('meta')->pluck('reminder_hours')->sort()->values()->all())->toBe([2, 24, 48]);
});

test('payments and mobile money transactions do not store provider api credentials', function () {
    $paymentColumns = Schema::getColumnListing('payments');
    $mobileMoneyColumns = Schema::getColumnListing('mobile_money_transactions');

    foreach (['api_key', 'api_secret', 'secret', 'token', 'credentials'] as $column) {
        expect($paymentColumns)->not->toContain($column);
        expect($mobileMoneyColumns)->not->toContain($column);
    }

    Payment::factory()->zaad()->create();
    $transaction = MobileMoneyTransaction::query()->first();

    expect($transaction)->not->toBeNull();

    foreach (['api_key', 'api_secret', 'secret', 'token', 'credentials'] as $attribute) {
        expect(array_key_exists($attribute, $transaction->getAttributes()))->toBeFalse();
    }
});
