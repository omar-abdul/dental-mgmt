<?php

namespace App\Console\Commands;

use App\Services\AppointmentReminderService;
use Illuminate\Console\Command;

class QueueAppointmentReminders extends Command
{
    /**
     * @var string
     */
    protected $signature = 'appointments:queue-reminders';

    /**
     * @var string
     */
    protected $description = 'Queue in-app would-send appointment reminders at configured horizons';

    public function handle(AppointmentReminderService $service): int
    {
        $queued = $service->queueDueReminders();

        $this->info("Queued {$queued} appointment reminder(s).");

        return self::SUCCESS;
    }
}
