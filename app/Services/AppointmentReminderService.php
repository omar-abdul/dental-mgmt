<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\CommunicationTemplate;
use Illuminate\Support\Carbon;

class AppointmentReminderService
{
    public const WOULD_SEND_ACTION = 'notification.would_send';

    public const REMINDER_TEMPLATE_CODE = 'APT-REMINDER';

    /**
     * @var list<AppointmentStatus>
     */
    private const REMINDABLE_STATUSES = [
        AppointmentStatus::Scheduled,
        AppointmentStatus::Confirmed,
    ];

    public function queueDueReminders(?Carbon $now = null): int
    {
        $now ??= now();
        $template = CommunicationTemplate::query()->find(self::REMINDER_TEMPLATE_CODE);

        if ($template === null) {
            return 0;
        }

        $queued = 0;

        foreach (config('notifications.appointment_reminder_hours', [48, 24, 2]) as $hours) {
            $windowStart = $now->copy()->addHours($hours)->startOfHour();
            $windowEnd = $windowStart->copy()->addHour()->subSecond();

            $appointments = Appointment::query()
                ->whereBetween('starts_at', [$windowStart, $windowEnd])
                ->whereIn('status', self::REMINDABLE_STATUSES)
                ->with('patient')
                ->get();

            foreach ($appointments as $appointment) {
                if ($this->hasQueuedReminder($appointment, $hours)) {
                    continue;
                }

                $body = $this->renderTemplate($template->body, $appointment);

                AuditLog::query()->create([
                    'action' => self::WOULD_SEND_ACTION,
                    'auditable_type' => Appointment::class,
                    'auditable_id' => $appointment->id,
                    'meta' => [
                        'template_code' => $template->code,
                        'channel' => $template->channel,
                        'reminder_hours' => $hours,
                        'body' => $body,
                        'patient_id' => $appointment->patient_id,
                    ],
                ]);

                $queued++;
            }
        }

        return $queued;
    }

    public function hasQueuedReminder(Appointment $appointment, int $reminderHours): bool
    {
        return AuditLog::query()
            ->where('action', self::WOULD_SEND_ACTION)
            ->where('auditable_type', Appointment::class)
            ->where('auditable_id', $appointment->id)
            ->where('meta->reminder_hours', $reminderHours)
            ->exists();
    }

    public function renderTemplate(string $body, Appointment $appointment, ?string $receiptNumber = null): string
    {
        $patient = $appointment->patient;
        $patientName = trim("{$patient->first_name} {$patient->last_name}");

        return str_replace(
            ['{patient_name}', '{date}', '{time}', '{receipt_number}'],
            [
                $patientName,
                $appointment->starts_at->timezone(config('app.timezone'))->format('Y-m-d'),
                $appointment->starts_at->timezone(config('app.timezone'))->format('H:i'),
                $receiptNumber ?? '',
            ],
            $body,
        );
    }
}
