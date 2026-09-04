<?php

namespace Database\Seeders;

use App\Models\CommunicationTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CommunicationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $dcms = json_decode(File::get(database_path('data/dcms.json')), true, 512, JSON_THROW_ON_ERROR);

        foreach ($dcms['communication_templates'] as $row) {
            CommunicationTemplate::query()->updateOrCreate(
                ['code' => $row['id']],
                [
                    'channel' => $row['channel'],
                    'name' => $row['name'],
                    'body' => $row['body'],
                ],
            );
        }
    }
}
