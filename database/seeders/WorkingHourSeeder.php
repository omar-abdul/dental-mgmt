<?php

namespace Database\Seeders;

use App\Models\WorkingHour;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class WorkingHourSeeder extends Seeder
{
    /**
     * @var array<string, int>
     */
    private const WEEKDAY_MAP = [
        'Sunday' => 0,
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6,
    ];

    public function run(): void
    {
        $dcms = json_decode(File::get(database_path('data/dcms.json')), true, 512, JSON_THROW_ON_ERROR);

        foreach ($dcms['working_hours'] as $row) {
            WorkingHour::query()->updateOrCreate(
                ['weekday' => self::WEEKDAY_MAP[$row['day']]],
                [
                    'opens_at' => $row['open'],
                    'closes_at' => $row['close'],
                ],
            );
        }
    }
}
