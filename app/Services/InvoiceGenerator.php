<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\TreatmentStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Treatment;
use App\Models\TreatmentProcedure;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class InvoiceGenerator
{
    public function __construct(
        private InvoiceNumberGenerator $numberGenerator,
    ) {}

    public function generateFromTreatment(Treatment $treatment, User $issuer): Invoice
    {
        if ($treatment->status !== TreatmentStatus::Completed) {
            throw ValidationException::withMessages([
                'treatment' => __('Invoice can only be generated from a completed treatment.'),
            ]);
        }

        if ($treatment->invoice()->exists()) {
            throw ValidationException::withMessages([
                'treatment' => __('An invoice already exists for this treatment.'),
            ]);
        }

        $treatment->load(['procedures.feeItem', 'patient']);

        if ($treatment->procedures->isEmpty()) {
            throw ValidationException::withMessages([
                'treatment' => __('Treatment has no procedures to invoice.'),
            ]);
        }

        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $this->createInvoice($treatment, $issuer);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === $maxAttempts) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('Unable to generate invoice.');
    }

    private function createInvoice(Treatment $treatment, User $issuer): Invoice
    {
        $subtotalCents = 0;
        $taxCents = 0;
        $lineItems = [];

        foreach ($treatment->procedures as $procedure) {
            /** @var TreatmentProcedure $procedure */
            $feeItem = $procedure->feeItem;
            $quantity = $procedure->quantity;
            $unitPriceCents = intdiv($procedure->fee_cents, $quantity);
            $lineSubtotalCents = $procedure->fee_cents;
            $lineTaxCents = intdiv($lineSubtotalCents * $feeItem->tax_rate_bps, 10000);
            $lineTotalCents = $lineSubtotalCents + $lineTaxCents;

            $subtotalCents += $lineSubtotalCents;
            $taxCents += $lineTaxCents;

            $lineItems[] = [
                'fee_item_id' => $feeItem->id,
                'description' => $feeItem->name,
                'quantity' => $quantity,
                'unit_price_cents' => $unitPriceCents,
                'discount_cents' => 0,
                'tax_cents' => $lineTaxCents,
                'line_total_cents' => $lineTotalCents,
            ];
        }

        $discountCents = 0;
        $totalCents = $subtotalCents - $discountCents + $taxCents;
        $userId = $issuer->id;

        $invoice = Invoice::query()->create([
            'invoice_number' => $this->numberGenerator->generate(),
            'patient_id' => $treatment->patient_id,
            'treatment_id' => $treatment->id,
            'issued_by' => $userId,
            'issued_at' => now(),
            'status' => InvoiceStatus::Issued,
            'subtotal_cents' => $subtotalCents,
            'discount_cents' => $discountCents,
            'tax_cents' => $taxCents,
            'total_cents' => $totalCents,
            'amount_paid_cents' => 0,
            'balance_cents' => $totalCents,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        foreach ($lineItems as $item) {
            InvoiceItem::query()->create([
                'invoice_id' => $invoice->id,
                ...$item,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }

        return $invoice->load(['items', 'patient', 'treatment', 'issuer']);
    }
}
