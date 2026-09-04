<?php

namespace Database\Factories;

use App\Models\CommunicationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunicationTemplate>
 */
class CommunicationTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'APT-REMINDER',
            'channel' => 'SMS',
            'name' => 'Appointment Reminder',
            'body' => 'Dear {patient_name}, this is a reminder of your dental appointment on {date} at {time}.',
        ];
    }

    public function appointmentReminder(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'APT-REMINDER',
            'channel' => 'SMS',
            'name' => 'Appointment Reminder',
            'body' => 'Dear {patient_name}, this is a reminder of your dental appointment on {date} at {time}.',
        ]);
    }
}
