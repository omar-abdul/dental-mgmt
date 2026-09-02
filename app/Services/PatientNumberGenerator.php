<?php

namespace App\Services;

use App\Models\Patient;

class PatientNumberGenerator
{
    public function generate(): string
    {
        $year = now()->format('Y');
        $prefix = "PAT-{$year}-";

        $lastNumber = Patient::withTrashed()
            ->where('patient_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('patient_number')
            ->value('patient_number');

        $sequence = 1;

        if ($lastNumber !== null) {
            $sequence = (int) substr($lastNumber, -5) + 1;
        }

        return sprintf('%s%05d', $prefix, $sequence);
    }
}
