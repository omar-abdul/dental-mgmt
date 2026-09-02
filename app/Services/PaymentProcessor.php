<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\MobileMoneyProvider;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\VerificationStatus;
use App\Models\Invoice;
use App\Models\MobileMoneyTransaction;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentProcessor
{
    public function __construct(
        private PaymentNumberGenerator $paymentNumberGenerator,
        private ReceiptNumberGenerator $receiptNumberGenerator,
    ) {}

    /**
     * @param  array{
     *     amount_cents: int,
     *     method: PaymentMethod,
     *     reference_number?: string|null,
     *     payer_phone?: string|null,
     *     transaction_id?: string|null,
     *     provider?: MobileMoneyProvider|null,
     *     verification_status?: VerificationStatus|null,
     * }  $data
     */
    public function pay(Invoice $invoice, User $receiver, array $data): Payment
    {
        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return DB::transaction(function () use ($invoice, $receiver, $data): Payment {
                    $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

                    if ($data['amount_cents'] > $lockedInvoice->balance_cents) {
                        throw ValidationException::withMessages([
                            'amount' => __('Payment amount exceeds invoice balance.'),
                        ]);
                    }

                    if ($lockedInvoice->balance_cents <= 0) {
                        throw ValidationException::withMessages([
                            'invoice' => __('Invoice has no outstanding balance.'),
                        ]);
                    }

                    $method = $data['method'];
                    $verificationStatus = $data['verification_status'] ?? null;
                    $completesPayment = $this->paymentCompletes($method, $verificationStatus);

                    $paymentStatus = match (true) {
                        $verificationStatus === VerificationStatus::Failed => PaymentStatus::Failed,
                        ! $completesPayment => PaymentStatus::Pending,
                        default => PaymentStatus::Completed,
                    };

                    $userId = $receiver->id;

                    $payment = Payment::query()->create([
                        'payment_number' => $this->paymentNumberGenerator->generate(),
                        'invoice_id' => $lockedInvoice->id,
                        'patient_id' => $lockedInvoice->patient_id,
                        'amount_cents' => $data['amount_cents'],
                        'method' => $method,
                        'status' => $paymentStatus,
                        'paid_at' => $completesPayment ? now() : null,
                        'received_by' => $userId,
                        'reference_number' => $data['reference_number'] ?? null,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);

                    if ($method->isMobileMoney()) {
                        $this->createMobileMoneyTransaction(
                            $payment,
                            $data,
                            $verificationStatus,
                            $userId,
                        );
                    }

                    if ($completesPayment) {
                        $this->applyPaymentToInvoice($lockedInvoice, $payment, $userId);
                    }

                    return $payment->load(['mobileMoneyTransaction', 'receipt']);
                });
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === $maxAttempts) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('Unable to process payment.');
    }

    public function refund(Invoice $invoice, Payment $originalPayment, User $receiver, int $amountCents): Payment
    {
        if ($originalPayment->invoice_id !== $invoice->id) {
            throw ValidationException::withMessages([
                'payment' => __('Payment does not belong to this invoice.'),
            ]);
        }

        if ($originalPayment->status !== PaymentStatus::Completed) {
            throw ValidationException::withMessages([
                'payment' => __('Only completed payments can be refunded.'),
            ]);
        }

        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return DB::transaction(function () use ($invoice, $originalPayment, $receiver, $amountCents): Payment {
                    $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

                    if ($amountCents > $originalPayment->amount_cents) {
                        throw ValidationException::withMessages([
                            'amount' => __('Refund amount exceeds original payment amount.'),
                        ]);
                    }

                    if ($amountCents > $lockedInvoice->amount_paid_cents) {
                        throw ValidationException::withMessages([
                            'amount' => __('Refund amount exceeds amount paid on invoice.'),
                        ]);
                    }

                    $existingRefundedCents = (int) Payment::query()
                        ->where('invoice_id', $lockedInvoice->id)
                        ->where('status', PaymentStatus::Refunded)
                        ->where('reference_number', $originalPayment->payment_number)
                        ->sum('amount_cents');

                    if ($existingRefundedCents + $amountCents > $originalPayment->amount_cents) {
                        throw ValidationException::withMessages([
                            'amount' => __('Refund amount exceeds remaining refundable amount for this payment.'),
                        ]);
                    }

                    $userId = $receiver->id;

                    $refundPayment = Payment::query()->create([
                        'payment_number' => $this->paymentNumberGenerator->generate(),
                        'invoice_id' => $lockedInvoice->id,
                        'patient_id' => $lockedInvoice->patient_id,
                        'amount_cents' => $amountCents,
                        'method' => $originalPayment->method,
                        'status' => PaymentStatus::Refunded,
                        'paid_at' => now(),
                        'received_by' => $userId,
                        'reference_number' => $originalPayment->payment_number,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);

                    $newAmountPaid = $lockedInvoice->amount_paid_cents - $amountCents;
                    $newBalance = $lockedInvoice->total_cents - $newAmountPaid;

                    $lockedInvoice->update([
                        'amount_paid_cents' => $newAmountPaid,
                        'balance_cents' => $newBalance,
                        'status' => $this->resolveStatusAfterRefund($lockedInvoice, $newAmountPaid),
                        'updated_by' => $userId,
                    ]);

                    return $refundPayment;
                });
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === $maxAttempts) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('Unable to process refund.');
    }

    private function paymentCompletes(PaymentMethod $method, ?VerificationStatus $verificationStatus): bool
    {
        if ($method->isMobileMoney()) {
            return $verificationStatus === VerificationStatus::Verified;
        }

        return true;
    }

    /**
     * @param  array{
     *     reference_number?: string|null,
     *     payer_phone?: string|null,
     *     transaction_id?: string|null,
     *     provider?: MobileMoneyProvider|null,
     * }  $data
     */
    private function createMobileMoneyTransaction(
        Payment $payment,
        array $data,
        ?VerificationStatus $verificationStatus,
        int $userId,
    ): void {
        $isVerified = $verificationStatus === VerificationStatus::Verified;

        MobileMoneyTransaction::query()->create([
            'payment_id' => $payment->id,
            'provider' => $data['provider'] ?? $payment->method->defaultProvider(),
            'payer_phone' => $data['payer_phone'] ?? '',
            'transaction_id' => $data['transaction_id'] ?? '',
            'reference_number' => $data['reference_number'] ?? '',
            'verification_status' => $verificationStatus ?? VerificationStatus::VerificationRequired,
            'verified_by' => $isVerified ? $userId : null,
            'verified_at' => $isVerified ? now() : null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    private function applyPaymentToInvoice(Invoice $invoice, Payment $payment, int $userId): void
    {
        $newAmountPaid = $invoice->amount_paid_cents + $payment->amount_cents;
        $newBalance = $invoice->total_cents - $newAmountPaid;

        $status = $newAmountPaid >= $invoice->total_cents
            ? InvoiceStatus::Paid
            : InvoiceStatus::PartiallyPaid;

        $invoice->update([
            'amount_paid_cents' => $newAmountPaid,
            'balance_cents' => $newBalance,
            'status' => $status,
            'updated_by' => $userId,
        ]);

        Receipt::query()->create([
            'receipt_number' => $this->receiptNumberGenerator->generate(),
            'payment_id' => $payment->id,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    private function resolveStatusAfterRefund(Invoice $invoice, int $newAmountPaid): InvoiceStatus
    {
        if ($newAmountPaid >= $invoice->total_cents) {
            return InvoiceStatus::Paid;
        }

        if ($newAmountPaid > 0) {
            return InvoiceStatus::PartiallyPaid;
        }

        $totalRefunded = Payment::query()
            ->where('invoice_id', $invoice->id)
            ->where('status', PaymentStatus::Refunded)
            ->sum('amount_cents');

        if ($totalRefunded >= $invoice->total_cents) {
            return InvoiceStatus::Refunded;
        }

        return InvoiceStatus::Issued;
    }
}
