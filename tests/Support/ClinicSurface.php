<?php

namespace Tests\Support;

use App\Models\Appointment;
use App\Models\AppointmentRevision;
use App\Models\CommunicationTemplate;
use App\Models\Dentist;
use App\Models\Encounter;
use App\Models\ImagingOrder;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\Receipt;
use App\Models\SoapNote;
use App\Models\Supplier;
use App\Models\Treatment;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\User;

final class ClinicSurface
{
    /**
     * @return array{
     *     admin: User,
     *     patient: Patient,
     *     dentist: Dentist,
     *     treatment: Treatment,
     *     encounter: Encounter,
     *     soapNote: SoapNote,
     *     invoice: Invoice,
     *     payment: Payment,
     *     receipt: Receipt,
     *     labOrder: LabOrder,
     *     imagingOrder: ImagingOrder,
     *     inventoryItem: InventoryItem,
     *     supplier: Supplier,
     *     purchaseOrder: PurchaseOrder,
     *     appointment: Appointment,
     *     template: CommunicationTemplate,
     *     treatmentPlan: TreatmentPlan,
     *     planItem: TreatmentPlanItem
     * }
     */
    public static function records(?User $admin = null): array
    {
        $admin ??= User::factory()->admin()->create();
        $patient = Patient::factory()->create([
            'first_name' => 'Visible',
            'last_name' => 'Patient',
        ]);
        $dentist = Dentist::factory()->create();
        $treatment = Treatment::factory()
            ->forPatient($patient)
            ->forDentist($dentist)
            ->completed()
            ->create([
                'diagnosis' => 'Coverage diagnosis',
            ]);
        $encounter = Encounter::factory()->forTreatment($treatment)->create();
        $soapNote = SoapNote::factory()->create([
            'encounter_id' => $encounter->id,
        ]);
        $invoice = Invoice::factory()->forPatient($patient)->create();
        $payment = Payment::factory()->forInvoice($invoice)->create();
        $receipt = Receipt::factory()->create([
            'payment_id' => $payment->id,
        ]);
        $labOrder = LabOrder::factory()->forPatient($patient)->forDentist($dentist)->create();
        $imagingOrder = ImagingOrder::factory()->forPatient($patient)->forDentist($dentist)->create();
        $inventoryItem = InventoryItem::factory()->create();
        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
        ]);
        $appointment = Appointment::factory()
            ->forPatient($patient)
            ->forDentist($dentist)
            ->create();
        $template = CommunicationTemplate::factory()->create([
            'code' => 'COV-'.fake()->unique()->numerify('#####'),
        ]);
        $treatmentPlan = TreatmentPlan::factory()->create([
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
        ]);
        $planItem = TreatmentPlanItem::factory()->create([
            'treatment_plan_id' => $treatmentPlan->id,
        ]);

        return compact(
            'admin',
            'patient',
            'dentist',
            'treatment',
            'encounter',
            'soapNote',
            'invoice',
            'payment',
            'receipt',
            'labOrder',
            'imagingOrder',
            'inventoryItem',
            'supplier',
            'purchaseOrder',
            'appointment',
            'template',
            'treatmentPlan',
            'planItem',
        );
    }

    public static function unsignedEncounter(): Encounter
    {
        $encounter = Encounter::factory()->create();
        SoapNote::factory()->create([
            'encounter_id' => $encounter->id,
            'signed_at' => null,
            'signed_by' => null,
        ]);

        return $encounter->fresh(['soapNote']);
    }

    public static function signedEncounter(): Encounter
    {
        $encounter = self::unsignedEncounter();
        $encounter->soapNote?->forceFill([
            'signed_at' => now(),
            'signed_by' => User::factory()->admin()->create()->id,
        ])->save();

        return $encounter->fresh(['soapNote']);
    }

    public static function appointmentRevision(): AppointmentRevision
    {
        $appointment = Appointment::factory()->create();

        return AppointmentRevision::query()->create([
            'appointment_id' => $appointment->id,
            'previous_starts_at' => $appointment->starts_at,
            'previous_ends_at' => $appointment->ends_at,
            'action' => 'cancelled',
            'created_by' => User::factory()->create()->id,
        ]);
    }
}
