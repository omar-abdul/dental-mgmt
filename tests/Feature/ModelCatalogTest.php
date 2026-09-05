<?php

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Chair;
use App\Models\CommunicationTemplate;
use App\Models\DailyCashClosing;
use App\Models\Dentist;
use App\Models\EmergencyContact;
use App\Models\Encounter;
use App\Models\Expense;
use App\Models\FeeItem;
use App\Models\ImageFile;
use App\Models\ImagingOrder;
use App\Models\ImagingResult;
use App\Models\Installment;
use App\Models\InsuranceClaim;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LabOrder;
use App\Models\MobileMoneyReconciliation;
use App\Models\MobileMoneyTransaction;
use App\Models\OdontogramSurface;
use App\Models\OdontogramTooth;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientCondition;
use App\Models\PatientMedication;
use App\Models\Payment;
use App\Models\PaymentPlan;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Receipt;
use App\Models\Room;
use App\Models\SoapNote;
use App\Models\SoapNoteAmendment;
use App\Models\Supplier;
use App\Models\ToothHistory;
use App\Models\Treatment;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\TreatmentProcedure;
use App\Models\User;
use App\Models\WorkingHour;
use Tests\Support\ClinicSurface;

test('each domain model can be persisted', function (string $label, Closure $create) {
    $model = $create();

    $this->assertModelExists($model);
})->with([
    'user' => ['user', fn () => User::factory()->create()],
    'room' => ['room', fn () => Room::factory()->create()],
    'chair' => ['chair', fn () => Chair::factory()->create()],
    'dentist' => ['dentist', fn () => Dentist::factory()->create()],
    'working hour' => ['working hour', fn () => WorkingHour::factory()->create(['weekday' => 1])],
    'patient' => ['patient', fn () => Patient::factory()->create()],
    'emergency contact' => ['emergency contact', fn () => EmergencyContact::factory()->create()],
    'patient allergy' => ['patient allergy', fn () => PatientAllergy::factory()->create()],
    'patient condition' => ['patient condition', fn () => PatientCondition::factory()->create()],
    'patient medication' => ['patient medication', fn () => PatientMedication::factory()->create()],
    'fee item' => ['fee item', fn () => FeeItem::factory()->create()],
    'appointment' => ['appointment', fn () => Appointment::factory()->create()],
    'appointment revision' => ['appointment revision', fn () => ClinicSurface::appointmentRevision()],
    'treatment' => ['treatment', fn () => Treatment::factory()->create()],
    'treatment procedure' => ['treatment procedure', fn () => TreatmentProcedure::factory()->create()],
    'prescription' => ['prescription', fn () => Prescription::factory()->create()],
    'prescription item' => ['prescription item', fn () => PrescriptionItem::factory()->create()],
    'invoice' => ['invoice', fn () => Invoice::factory()->create()],
    'invoice item' => ['invoice item', fn () => InvoiceItem::factory()->create()],
    'payment' => ['payment', fn () => Payment::factory()->create()],
    'mobile money transaction' => ['mobile money transaction', fn () => MobileMoneyTransaction::factory()->create()],
    'receipt' => ['receipt', fn () => Receipt::factory()->create()],
    'inventory item' => ['inventory item', fn () => InventoryItem::factory()->create()],
    'inventory movement' => ['inventory movement', fn () => InventoryMovement::factory()->create(['delta' => 2])],
    'inventory batch' => ['inventory batch', fn () => InventoryBatch::factory()->create()],
    'supplier' => ['supplier', fn () => Supplier::factory()->create()],
    'purchase order' => ['purchase order', fn () => PurchaseOrder::factory()->create()],
    'purchase order item' => ['purchase order item', fn () => PurchaseOrderItem::factory()->create()],
    'activity log' => ['activity log', fn () => ActivityLog::factory()->create()],
    'audit log' => ['audit log', fn () => AuditLog::factory()->create()],
    'encounter' => ['encounter', fn () => Encounter::factory()->create()],
    'soap note' => ['soap note', fn () => SoapNote::factory()->create()],
    'soap note amendment' => ['soap note amendment', fn () => SoapNoteAmendment::factory()->create()],
    'odontogram tooth' => ['odontogram tooth', fn () => OdontogramTooth::factory()->create()],
    'odontogram surface' => ['odontogram surface', fn () => OdontogramSurface::factory()->create()],
    'tooth history' => ['tooth history', fn () => ToothHistory::factory()->create()],
    'treatment plan' => ['treatment plan', fn () => TreatmentPlan::factory()->create()],
    'treatment plan item' => ['treatment plan item', fn () => TreatmentPlanItem::factory()->create()],
    'lab order' => ['lab order', fn () => LabOrder::factory()->create()],
    'imaging order' => ['imaging order', fn () => ImagingOrder::factory()->create()],
    'imaging result' => ['imaging result', fn () => ImagingResult::factory()->create()],
    'image file' => ['image file', fn () => ImageFile::factory()->create()],
    'expense' => ['expense', fn () => Expense::factory()->create()],
    'daily cash closing' => ['daily cash closing', fn () => DailyCashClosing::factory()->create()],
    'payment plan' => ['payment plan', fn () => PaymentPlan::factory()->create()],
    'installment' => ['installment', fn () => Installment::factory()->create()],
    'insurance claim' => ['insurance claim', fn () => InsuranceClaim::factory()->create()],
    'mobile money reconciliation' => ['mobile money reconciliation', fn () => MobileMoneyReconciliation::factory()->create()],
    'communication template' => ['communication template', fn () => CommunicationTemplate::factory()->create([
        'code' => 'MOD-'.fake()->unique()->numerify('#####'),
    ])],
]);
