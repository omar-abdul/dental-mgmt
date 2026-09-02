<?php

namespace App\Services;

use App\Models\Prescription;
use Illuminate\Support\Facades\DB;

class PrescriptionNumberGenerator
{
    public function generate(): string
    {
        $year = now()->format('Y');
        $prefix = "RX-{$year}-";
        $lockName = "prescription_number_{$year}";
        $lockAcquired = false;

        if (DB::connection()->getDriverName() === 'mysql') {
            $lockAcquired = (int) DB::selectOne('SELECT GET_LOCK(?, 10) AS acquired', [$lockName])->acquired === 1;

            if (! $lockAcquired) {
                throw new \RuntimeException('Unable to acquire prescription number lock.');
            }
        }

        try {
            $lastNumber = Prescription::query()
                ->where('number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('number')
                ->value('number');

            $sequence = 1;

            if ($lastNumber !== null) {
                $sequence = (int) substr($lastNumber, -5) + 1;
            }

            return sprintf('%s%05d', $prefix, $sequence);
        } finally {
            if ($lockAcquired) {
                DB::selectOne('SELECT RELEASE_LOCK(?)', [$lockName]);
            }
        }
    }
}
