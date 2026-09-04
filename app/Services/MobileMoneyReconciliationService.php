<?php

namespace App\Services;

use App\Enums\MobileMoneyProvider;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Carbon;

class MobileMoneyReconciliationService
{
    /**
     * @return array{transaction_count: int, system_total_cents: int}
     */
    public function systemTotalsForDateAndProvider(Carbon $date, MobileMoneyProvider $provider): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $mobileMoneyMethods = [
            PaymentMethod::Zaad,
            PaymentMethod::Sahal,
            PaymentMethod::Edahab,
            PaymentMethod::Mycash,
        ];

        $payments = Payment::query()
            ->whereIn('method', $mobileMoneyMethods)
            ->where('status', PaymentStatus::Completed)
            ->whereBetween('paid_at', [$start, $end])
            ->whereHas('mobileMoneyTransaction', fn ($query) => $query->where('provider', $provider))
            ->get();

        $refundCents = (int) Payment::query()
            ->whereIn('method', $mobileMoneyMethods)
            ->where('status', PaymentStatus::Refunded)
            ->whereBetween('paid_at', [$start, $end])
            ->whereExists(function ($query) use ($provider): void {
                $query->selectRaw('1')
                    ->from('payments as original_payments')
                    ->join('mobile_money_transactions', 'mobile_money_transactions.payment_id', '=', 'original_payments.id')
                    ->whereColumn('original_payments.payment_number', 'payments.reference_number')
                    ->where('mobile_money_transactions.provider', $provider);
            })
            ->sum('amount_cents');

        return [
            'transaction_count' => $payments->count(),
            'system_total_cents' => (int) $payments->sum('amount_cents') - $refundCents,
        ];
    }
}
