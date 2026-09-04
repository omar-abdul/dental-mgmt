<?php

namespace App\Services;

use App\Models\Encounter;
use App\Models\SoapNote;
use App\Models\Treatment;
use Illuminate\Support\Facades\DB;

class EncounterCreator
{
    public function __construct(
        private EncounterNumberGenerator $numberGenerator,
    ) {}

    public function createForCompletedTreatment(Treatment $treatment, ?int $userId): ?Encounter
    {
        if ($treatment->encounter()->exists()) {
            return $treatment->encounter;
        }

        return DB::transaction(function () use ($treatment, $userId): Encounter {
            $treatment->loadMissing(['patient', 'dentist']);

            $encounter = Encounter::query()->create([
                'number' => $this->numberGenerator->generate(),
                'patient_id' => $treatment->patient_id,
                'dentist_id' => $treatment->dentist_id,
                'appointment_id' => $treatment->appointment_id,
                'treatment_id' => $treatment->id,
                'visited_at' => $treatment->diagnosed_at,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            SoapNote::query()->create([
                'encounter_id' => $encounter->id,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            return $encounter;
        });
    }
}
