<?php

namespace App\Concerns;

trait ConvertsDollarAmounts
{
    protected function dollarsToCents(string|int|float|null $amount): int
    {
        $normalized = trim((string) $amount);

        if ($normalized === '') {
            return 0;
        }

        $negative = str_starts_with($normalized, '-');

        if ($negative) {
            $normalized = ltrim($normalized, '-');
        }

        if (str_contains($normalized, '.')) {
            [$dollars, $fraction] = explode('.', $normalized, 2);
            $fraction = str_pad(substr($fraction, 0, 2), 2, '0');
        } else {
            $dollars = $normalized;
            $fraction = '00';
        }

        $cents = ((int) $dollars * 100) + (int) $fraction;

        return $negative ? -$cents : $cents;
    }
}
