<?php

namespace App\Services;

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
        $defaultFrom = $now->copy()->startOfMonth()->toDateString();
        $defaultTo = $now->copy()->toDateString();

        $fromInput = (string) $request->query('from', $defaultFrom);
        $toInput = (string) $request->query('to', $defaultTo);

        $from = Carbon::createFromFormat('Y-m-d', $fromInput)?->startOfDay()
            ?? $now->copy()->startOfMonth()->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $toInput)?->endOfDay()
            ?? $now->copy()->endOfDay();

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
}
