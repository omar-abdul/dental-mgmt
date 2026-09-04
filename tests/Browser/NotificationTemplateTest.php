<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\CommunicationTemplate;
use App\Models\User;
use App\Services\AppointmentReminderService;
use Database\Seeders\CommunicationTemplateSeeder;
use Illuminate\Support\Facades\Http;

test('admin can edit a notification template and scheduler writes would-send audit', function () {
    Http::fake();
    $this->seed(CommunicationTemplateSeeder::class);

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $page = visit(route('notification-templates.index'));

    $page->assertSee('Notification templates')
        ->assertSee('APT-REMINDER')
        ->fill('@template-body-APT-REMINDER', 'Browser updated reminder for {patient_name}.')
        ->click('@save-template-APT-REMINDER')
        ->assertSee('Browser updated reminder for {patient_name}.')
        ->assertNoJavaScriptErrors();

    expect(CommunicationTemplate::query()->find('APT-REMINDER')?->body)
        ->toBe('Browser updated reminder for {patient_name}.');

    $now = now()->startOfHour();
    $this->travelTo($now);

    $appointment = Appointment::factory()->create([
        'starts_at' => $now->copy()->addHours(2),
        'ends_at' => $now->copy()->addHours(2)->addMinutes(30),
        'status' => AppointmentStatus::Confirmed,
    ]);

    $this->artisan('appointments:queue-reminders')->assertSuccessful();

    $this->assertDatabaseHas('audit_logs', [
        'action' => AppointmentReminderService::WOULD_SEND_ACTION,
        'auditable_type' => Appointment::class,
        'auditable_id' => $appointment->id,
    ]);

    expect(AuditLog::query()->first()?->meta['body'])
        ->toContain('Browser updated reminder');

    Http::assertNothingSent();
});
