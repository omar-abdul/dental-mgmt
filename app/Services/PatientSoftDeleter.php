<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\EmergencyContact;
use App\Models\Encounter;
use App\Models\ImagingOrder;
use App\Models\Invoice;
use App\Models\LabOrder;
use App\Models\OdontogramTooth;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientCondition;
use App\Models\PatientMedication;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\ToothHistory;
use App\Models\Treatment;
use App\Models\TreatmentPlan;
use Illuminate\Support\Facades\DB;

class PatientSoftDeleter
{
    public function delete(Patient $patient, ?int $userId, ?string $ip): void
    {
        DB::transaction(function () use ($patient, $userId, $ip): void {
            $lockedPatient = Patient::query()
                ->whereKey($patient->id)
                ->lockForUpdate()
                ->firstOrFail();

            Appointment::query()->where('patient_id', $lockedPatient->id)->delete();
            Treatment::query()->where('patient_id', $lockedPatient->id)->delete();
            Prescription::query()->where('patient_id', $lockedPatient->id)->delete();
            Invoice::query()->where('patient_id', $lockedPatient->id)->delete();
            Payment::query()->where('patient_id', $lockedPatient->id)->delete();
            Encounter::query()->where('patient_id', $lockedPatient->id)->delete();
            LabOrder::query()->where('patient_id', $lockedPatient->id)->delete();
            ImagingOrder::query()->where('patient_id', $lockedPatient->id)->delete();
            TreatmentPlan::query()->where('patient_id', $lockedPatient->id)->delete();
            OdontogramTooth::query()->where('patient_id', $lockedPatient->id)->delete();
            ToothHistory::query()->where('patient_id', $lockedPatient->id)->delete();
            EmergencyContact::query()->where('patient_id', $lockedPatient->id)->delete();
            PatientAllergy::query()->where('patient_id', $lockedPatient->id)->delete();
            PatientCondition::query()->where('patient_id', $lockedPatient->id)->delete();
            PatientMedication::query()->where('patient_id', $lockedPatient->id)->delete();

            AuditLog::query()->create([
                'action' => 'patient.deleted',
                'auditable_type' => Patient::class,
                'auditable_id' => $lockedPatient->id,
                'user_id' => $userId,
                'ip' => $ip,
            ]);

            $lockedPatient->delete();
        });
    }
}
