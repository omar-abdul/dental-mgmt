<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentNumberGenerator
{
    public function generate(): string
    {
        $year = now()->format('Y');
        $prefix = "PAY-{$year}-";
        $lockName = "payment_number_{$year}";
        $lockAcquired = false;

        if (DB::connection()->getDriverName() === 'mysql') {
            $lockAcquired = (int) DB::selectOne('SELECT GET_LOCK(?, 10) AS acquired', [$lockName])->acquired === 1;

            if (! $lockAcquired) {
                throw new \RuntimeException('Unable to acquire payment number lock.');
            }
        }

        try {
            $lastNumber = Payment::query()
                ->where('payment_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('payment_number')
                ->value('payment_number');

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
