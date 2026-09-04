<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Carbon;

class DailyCashClosingService
{
    public function systemCashTotalCentsForDate(Carbon $date): int
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $inflowCents = (int) Payment::query()
            ->where('method', PaymentMethod::Cash)
            ->where('status', PaymentStatus::Completed)
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount_cents');

        $refundCents = (int) Payment::query()
            ->where('method', PaymentMethod::Cash)
            ->where('status', PaymentStatus::Refunded)
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount_cents');

        return $inflowCents - $refundCents;
    }
}
