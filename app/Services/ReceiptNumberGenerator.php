<?php

namespace App\Services;

use App\Models\Receipt;
use Illuminate\Support\Facades\DB;

class ReceiptNumberGenerator
{
    public function generate(): string
    {
        $year = now()->format('Y');
        $prefix = "RCT-{$year}-";
        $lockName = "receipt_number_{$year}";
        $lockAcquired = false;

        if (DB::connection()->getDriverName() === 'mysql') {
            $lockAcquired = (int) DB::selectOne('SELECT GET_LOCK(?, 10) AS acquired', [$lockName])->acquired === 1;

            if (! $lockAcquired) {
                throw new \RuntimeException('Unable to acquire receipt number lock.');
            }
        }

        try {
            $lastNumber = Receipt::query()
                ->where('receipt_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('receipt_number')
                ->value('receipt_number');

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
