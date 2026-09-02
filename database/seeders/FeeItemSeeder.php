<?php

namespace Database\Seeders;

use App\Models\FeeItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class FeeItemSeeder extends Seeder
{
    /**
     * @var array<string, array{calendar_color: string, default_duration_minutes: int}>
     */
    private const METADATA = [
        'CONSULT' => ['calendar_color' => '#3B82F6', 'default_duration_minutes' => 30],
        'EXAM' => ['calendar_color' => '#10B981', 'default_duration_minutes' => 30],
        'CLEAN' => ['calendar_color' => '#06B6D4', 'default_duration_minutes' => 45],
        'FILL' => ['calendar_color' => '#F59E0B', 'default_duration_minutes' => 60],
        'RCT' => ['calendar_color' => '#EF4444', 'default_duration_minutes' => 90],
        'EXT' => ['calendar_color' => '#8B5CF6', 'default_duration_minutes' => 45],
        'CROWN' => ['calendar_color' => '#EC4899', 'default_duration_minutes' => 60],
        'IMPLANT' => ['calendar_color' => '#6366F1', 'default_duration_minutes' => 120],
        'XRAY' => ['calendar_color' => '#64748B', 'default_duration_minutes' => 15],
    ];

    public function run(): void
    {
        $dcms = json_decode(File::get(database_path('data/dcms.json')), true, 512, JSON_THROW_ON_ERROR);
        $feeItems = $dcms['billing']['fee_items'];

        foreach ($feeItems as $item) {
            $code = $item['code'];
            $meta = self::METADATA[$code];

            FeeItem::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'unit' => $item['unit'],
                    'price_cents' => (int) round($item['price'] * 100),
                    'tax_rate_bps' => (int) round($item['tax_rate'] * 100),
                    'calendar_color' => $meta['calendar_color'],
                    'default_duration_minutes' => $meta['default_duration_minutes'],
                    'is_active' => $item['active'],
                ],
            );
        }
    }
}
