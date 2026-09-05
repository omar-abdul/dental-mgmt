<?php

namespace App\Services;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportDateRange
{
    public function __construct(
        public readonly Carbon $from,
        public readonly Carbon $to,
    ) {}

    public static function fromRequest(Request $request, ?Carbon $now = null): self
    {
        $now ??= Carbon::now();
        $defaultFrom = $now->copy()->startOfMonth()->startOfDay();
        $defaultTo = $now->copy()->endOfDay();

        $from = self::parseDay((string) $request->query('from', ''), $defaultFrom, endOfDay: false);
        $to = self::parseDay((string) $request->query('to', ''), $defaultTo, endOfDay: true);

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return new self($from, $to);
    }

    /**
     * @return array{from: string, to: string}
     */
    public function toFilterProps(): array
    {
        return [
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
        ];
    }

    private static function parseDay(string $value, Carbon $fallback, bool $endOfDay): Carbon
    {
        if ($value === '') {
            return $fallback;
        }

        try {
            $parsed = Carbon::createFromFormat('!Y-m-d', $value);
        } catch (InvalidFormatException) {
            return $fallback;
        }

        if ($parsed === null || $parsed->format('Y-m-d') !== $value) {
            return $fallback;
        }

        return $endOfDay ? $parsed->copy()->endOfDay() : $parsed->copy()->startOfDay();
    }
}
