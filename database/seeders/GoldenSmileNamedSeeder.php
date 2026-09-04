<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\ClinicRole;
use App\Enums\Gender;
use App\Enums\InventoryCategory;
use App\Enums\InvoiceStatus;
use App\Enums\MobileMoneyProvider;
use App\Enums\PatientStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TreatmentStatus;
use App\Enums\VerificationStatus;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Chair;
use App\Models\Dentist;
use App\Models\EmergencyContact;
use App\Models\FeeItem;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MobileMoneyTransaction;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientCondition;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Receipt;
use App\Models\Room;
use App\Models\Treatment;
use App\Models\TreatmentProcedure;
use App\Models\User;
use Database\Seeders\GoldenSmile\GoldenSmileFixture;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GoldenSmileNamedSeeder extends Seeder
{
    /**
     * @var array<string, mixed>
     */
    private array $fixture;

    /**
     * @var array<string, User>
     */
    private array $users = [];

    /**
     * @var array<string, Room>
     */
    private array $rooms = [];

    /**
     * @var array<string, Chair>
     */
    private array $chairs = [];

    /**
     * @var array<string, Dentist>
     */
    private array $dentists = [];

    /**
     * @var array<string, Patient>
     */
    private array $patients = [];

    /**
     * @var array<string, FeeItem>
     */
    private array $feeItems = [];

    /**
     * @var array<string, Appointment>
     */
    private array $appointments = [];

    /**
     * @var array<string, Treatment>
     */
    private array $treatments = [];

    /**
     * @var array<string, Invoice>
     */
    private array $invoices = [];

    /**
     * @var array<string, InventoryItem>
     */
    private array $inventoryItems = [];

    public function run(): void
    {
        $this->fixture = GoldenSmileFixture::data();
        $password = GoldenSmileFixture::demoPassword();

        $this->loadFeeItems();
        $this->seedUsers($password);
        $this->seedRooms();
        $this->seedChairs();
        $this->seedDentists();
        $this->seedPatients();
        $this->seedAppointments();
        $this->seedTreatments();
        $this->seedInvoices();
        $this->seedInventoryItems();
        $this->seedActivityLogs();
    }

    private function loadFeeItems(): void
    {
        $codesByKey = GoldenSmileFixture::feeCodesByKey();

        foreach ($codesByKey as $key => $code) {
            $feeItem = FeeItem::query()->where('code', $code)->first();

            if ($feeItem === null) {
                continue;
            }

            $this->feeItems[$key] = $feeItem;
        }
    }

    private function seedUsers(string $password): void
    {
        foreach ($this->fixture['users'] as $row) {
            $this->users[$row['key']] = User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => Hash::make($password),
                    'role' => ClinicRole::from($row['role']),
                    'email_verified_at' => now(),
                ],
            );
        }
    }

    private function seedRooms(): void
    {
        foreach ($this->fixture['rooms'] as $row) {
            $this->rooms[$row['key']] = Room::query()->updateOrCreate(
                ['name' => $row['name']],
                [
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedChairs(): void
    {
        foreach ($this->fixture['chairs'] as $row) {
            $this->chairs[$row['key']] = Chair::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'room_id' => $this->rooms[$row['room_key']]->id,
                    'name' => $row['name'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedDentists(): void
    {
        foreach ($this->fixture['dentists'] as $row) {
            $user = $this->users[$row['user_key']];
            $defaultChair = $this->chairs[$row['default_chair_key']];

            $this->dentists[$row['key']] = Dentist::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'display_name' => $row['display_name'],
                    'default_chair_id' => $defaultChair->id,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedPatients(): void
    {
        foreach ($this->fixture['patients'] as $row) {
            $patient = Patient::query()->updateOrCreate(
                ['patient_number' => $row['patient_number']],
                [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'date_of_birth' => $row['date_of_birth'],
                    'gender' => Gender::from($row['gender']),
                    'phone' => $row['phone'],
                    'email' => $row['email'],
                    'occupation' => $row['occupation'],
                    'address' => $row['address'],
                    'referred_by' => $row['referred_by'],
                    'insurance_provider' => $row['insurance_provider'],
                    'status' => PatientStatus::from($row['status']),
                ],
            );

            $this->patients[$row['key']] = $patient;

            $patient->allergies()->delete();
            foreach ($row['allergies'] as $allergy) {
                PatientAllergy::query()->create([
                    'patient_id' => $patient->id,
                    'label' => $allergy['label'],
                    'is_critical' => $allergy['is_critical'],
                ]);
            }

            $patient->conditions()->delete();
            foreach ($row['conditions'] as $condition) {
                PatientCondition::query()->create([
                    'patient_id' => $patient->id,
                    'label' => $condition['label'],
                    'is_critical' => $condition['is_critical'],
                ]);
            }

            $patient->emergencyContacts()->delete();
            foreach ($row['emergency_contacts'] as $contact) {
                EmergencyContact::query()->create([
                    'patient_id' => $patient->id,
                    'name' => $contact['name'],
                    'relationship' => $contact['relationship'],
                    'phone' => $contact['phone'],
                ]);
            }
        }
    }

    private function seedAppointments(): void
    {
        $sequence = 1;

        foreach ($this->fixture['appointments'] as $row) {
            $startsAt = GoldenSmileFixture::parseWhen($row['when']);
            $endsAt = $startsAt->copy()->addMinutes($row['duration_minutes']);
            $feeItem = $this->feeItems[$row['fee_item_key']] ?? null;
            $number = $row['number'] ?? sprintf('APT-%s-%05d', now()->format('Y'), $sequence);
            $sequence++;

            $this->appointments[$row['key']] = Appointment::query()->updateOrCreate(
                ['number' => $number],
                [
                    'number' => $number,
                    'patient_id' => $this->patients[$row['patient_key']]->id,
                    'dentist_id' => $this->dentists[$row['dentist_key']]->id,
                    'chair_id' => $this->chairs[$row['chair_key']]->id,
                    'fee_item_id' => $feeItem?->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => AppointmentStatus::from($row['status']),
                    'reason' => $row['reason'] ?? null,
                ],
            );
        }
    }

    private function seedTreatments(): void
    {
        foreach ($this->fixture['treatments'] as $row) {
            $appointment = $this->appointments[$row['appointment_key']] ?? null;
            $completedAt = isset($row['completed_relative'])
                ? GoldenSmileFixture::parseRelative($row['completed_relative'])
                : now();

            $treatment = Treatment::query()->updateOrCreate(
                [
                    'patient_id' => $this->patients[$row['patient_key']]->id,
                    'appointment_id' => $appointment?->id,
                ],
                [
                    'dentist_id' => $this->dentists[$row['dentist_key']]->id,
                    'diagnosed_at' => $completedAt,
                    'diagnosis' => $row['diagnosis'],
                    'status' => TreatmentStatus::from($row['status']),
                ],
            );

            $this->treatments[$row['key']] = $treatment;

            $treatment->procedures()->delete();
            foreach ($row['procedures'] as $procedure) {
                TreatmentProcedure::query()->create([
                    'treatment_id' => $treatment->id,
                    'fee_item_id' => $this->feeItems[$procedure['fee_item_key']]->id,
                    'tooth_fdi' => $procedure['tooth_fdi'] ?? null,
                    'quantity' => $procedure['quantity'],
                    'fee_cents' => $procedure['fee_cents'],
                ]);
            }

            if ($row['prescriptions'] ?? [] !== []) {
                $prescription = Prescription::query()->updateOrCreate(
                    ['treatment_id' => $treatment->id],
                    [
                        'number' => sprintf('RX-%s-%05d', now()->format('Y'), $treatment->id),
                        'patient_id' => $treatment->patient_id,
                        'prescriber_id' => $this->dentists[$row['dentist_key']]->user_id,
                        'prescribed_at' => $completedAt,
                    ],
                );

                $prescription->items()->delete();
                foreach ($row['prescriptions'] as $item) {
                    PrescriptionItem::query()->create([
                        'prescription_id' => $prescription->id,
                        'medication' => $item['medication'],
                        'dosage' => $item['dosage'],
                        'instructions' => $item['instructions'],
                    ]);
                }
            }
        }
    }

    private function seedInvoices(): void
    {
        $issuer = $this->users['admin-santos'];

        foreach ($this->fixture['invoices'] as $row) {
            $patient = $this->patients[$row['patient_key']];
            $subtotalCents = (int) collect($row['items'])->sum(
                fn (array $item): int => $item['quantity'] * $item['unit_price_cents'],
            );
            $status = InvoiceStatus::from($row['status']);
            $amountPaidCents = $status === InvoiceStatus::Paid ? $subtotalCents : 0;
            $balanceCents = $subtotalCents - $amountPaidCents;

            $invoice = Invoice::query()->updateOrCreate(
                ['invoice_number' => $row['invoice_number']],
                [
                    'patient_id' => $patient->id,
                    'treatment_id' => null,
                    'issued_by' => $issuer->id,
                    'issued_at' => now()->subHour(),
                    'status' => $status,
                    'subtotal_cents' => $subtotalCents,
                    'discount_cents' => 0,
                    'tax_cents' => 0,
                    'total_cents' => $subtotalCents,
                    'amount_paid_cents' => $amountPaidCents,
                    'balance_cents' => $balanceCents,
                ],
            );

            $this->invoices[$row['key']] = $invoice;

            $invoice->items()->delete();
            foreach ($row['items'] as $item) {
                InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'fee_item_id' => $this->feeItems[$item['fee_item_key']]->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price_cents' => $item['unit_price_cents'],
                    'discount_cents' => 0,
                    'tax_cents' => 0,
                    'line_total_cents' => $item['quantity'] * $item['unit_price_cents'],
                ]);
            }

            $invoice->payments()->delete();
            foreach ($row['payments'] ?? [] as $paymentRow) {
                $paidAt = isset($paymentRow['paid_relative'])
                    ? GoldenSmileFixture::parseRelative($paymentRow['paid_relative'])
                    : now();

                $payment = Payment::query()->create([
                    'payment_number' => $paymentRow['payment_number'],
                    'invoice_id' => $invoice->id,
                    'patient_id' => $patient->id,
                    'amount_cents' => $paymentRow['amount_cents'],
                    'method' => PaymentMethod::from($paymentRow['method']),
                    'status' => PaymentStatus::from($paymentRow['status']),
                    'paid_at' => $paidAt,
                    'received_by' => $issuer->id,
                    'reference_number' => $paymentRow['mobile_money']['reference_number'] ?? null,
                ]);

                if (isset($paymentRow['receipt_number'])) {
                    Receipt::query()->updateOrCreate(
                        ['receipt_number' => $paymentRow['receipt_number']],
                        ['payment_id' => $payment->id],
                    );
                }

                if (isset($paymentRow['mobile_money'])) {
                    $mobile = $paymentRow['mobile_money'];
                    MobileMoneyTransaction::query()->updateOrCreate(
                        ['payment_id' => $payment->id],
                        [
                            'provider' => MobileMoneyProvider::from($mobile['provider']),
                            'payer_phone' => $mobile['payer_phone'],
                            'transaction_id' => $mobile['transaction_id'],
                            'reference_number' => $mobile['reference_number'],
                            'verification_status' => VerificationStatus::from($mobile['verification_status']),
                            'verified_by' => $issuer->id,
                            'verified_at' => $paidAt,
                        ],
                    );
                }
            }
        }
    }

    private function seedInventoryItems(): void
    {
        foreach ($this->fixture['inventory_items'] as $row) {
            $this->inventoryItems[$row['key']] = InventoryItem::query()->updateOrCreate(
                ['name' => $row['name']],
                [
                    'category' => $this->mapInventoryCategory($row['category']),
                    'quantity' => $row['quantity'],
                    'unit' => $row['unit'],
                    'reorder_level' => $row['reorder_level'],
                    'unit_cost_cents' => $row['unit_cost_cents'],
                ],
            );
        }
    }

    private function seedActivityLogs(): void
    {
        ActivityLog::query()->delete();

        foreach ($this->fixture['activity'] as $row) {
            ActivityLog::query()->create([
                'user_id' => $this->users['admin-santos']->id,
                'action' => $row['type'],
                'description' => $row['description'],
                'created_at' => GoldenSmileFixture::parseRelative($row['relative']),
                'updated_at' => GoldenSmileFixture::parseRelative($row['relative']),
            ]);
        }
    }

    private function mapInventoryCategory(string $category): InventoryCategory
    {
        return match ($category) {
            'PPE' => InventoryCategory::Ppe,
            'Dental Materials' => InventoryCategory::DentalMaterials,
            'Medicines' => InventoryCategory::Medicines,
            'Instruments' => InventoryCategory::Instruments,
            'Consumables' => InventoryCategory::Consumables,
            'Office Supplies' => InventoryCategory::OfficeSupplies,
            default => InventoryCategory::Consumables,
        };
    }
}
