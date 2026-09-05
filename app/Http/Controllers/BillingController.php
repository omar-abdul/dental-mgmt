<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\StoreRefundRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Treatment;
use App\Services\InvoiceGenerator;
use App\Services\PaymentProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Invoice::class);

        $search = trim((string) $request->query('search', ''));

        $invoices = Invoice::query()
            ->with(['patient'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('patient', function ($query) use ($search): void {
                            $query->where('patient_number', 'like', "%{$search}%")
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                        });
                });
            })
            ->orderByDesc('issued_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Invoice $invoice) => $this->invoiceListItem($invoice));

        return Inertia::render('billing/Index', [
            'invoices' => $invoices,
            'search' => $search,
        ]);
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        Gate::authorize('view', $invoice);

        $invoice->load([
            'patient',
            'treatment',
            'issuer',
            'items.feeItem',
            'payments.receiver',
            'payments.receipt',
            'payments.mobileMoneyTransaction',
        ]);

        return Inertia::render('billing/Show', [
            'invoice' => $this->invoiceDetail($invoice),
            'canPay' => $request->user()?->can('pay', $invoice) ?? false,
            'canRefund' => $request->user()?->can('refund', $invoice) ?? false,
            'paymentMethods' => $this->paymentMethodOptions(),
            'mobileMoneyProviders' => $this->mobileMoneyProviderOptions(),
            'verificationStatuses' => $this->verificationStatusOptions(),
        ]);
    }

    public function generateFromTreatment(Request $request, Treatment $treatment, InvoiceGenerator $generator): RedirectResponse
    {
        Gate::authorize('generate', Invoice::class);

        $user = $request->user();

        abort_unless($user !== null, 403);

        $invoice = $generator->generateFromTreatment($treatment, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice generated.')]);

        return to_route('billing.show', $invoice);
    }

    public function pay(StorePaymentRequest $request, Invoice $invoice, PaymentProcessor $processor): RedirectResponse
    {
        Gate::authorize('pay', $invoice);

        $payment = $processor->pay($invoice, $request->user(), $request->paymentData());

        if ($payment->receipt !== null) {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment recorded. Receipt issued.')]);

            return to_route('billing.receipts.show', $payment->receipt);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment recorded.')]);

        return to_route('billing.show', $invoice);
    }

    public function refund(StoreRefundRequest $request, Invoice $invoice, PaymentProcessor $processor): RedirectResponse
    {
        Gate::authorize('refund', $invoice);

        $processor->refund(
            $invoice,
            $request->originalPayment(),
            $request->user(),
            $request->amountCents(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Refund processed.')]);

        return to_route('billing.show', $invoice);
    }

    public function showReceipt(Request $request, Receipt $receipt): Response
    {
        $receipt->load([
            'payment.invoice.patient',
            'payment.invoice.items',
            'payment.receiver',
            'payment.mobileMoneyTransaction',
        ]);

        Gate::authorize('view', $receipt->payment->invoice);

        return Inertia::render('billing/Receipt', [
            'receipt' => $this->receiptDetail($receipt),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceListItem(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status->value,
            'status_label' => $this->statusLabel($invoice->status->value),
            'issued_at_formatted' => $invoice->issued_at->format('M j, Y'),
            'patient_name' => "{$invoice->patient->first_name} {$invoice->patient->last_name}",
            'patient_number' => $invoice->patient->patient_number,
            'total_cents' => $invoice->total_cents,
            'total_formatted' => $this->formatCents($invoice->total_cents),
            'balance_cents' => $invoice->balance_cents,
            'balance_formatted' => $this->formatCents($invoice->balance_cents),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceDetail(Invoice $invoice): array
    {
        $refundedByOriginal = Payment::query()
            ->where('invoice_id', $invoice->id)
            ->where('status', PaymentStatus::Refunded)
            ->selectRaw('reference_number, SUM(amount_cents) as refunded_cents')
            ->groupBy('reference_number')
            ->pluck('refunded_cents', 'reference_number');

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status->value,
            'status_label' => $this->statusLabel($invoice->status->value),
            'issued_at_formatted' => $invoice->issued_at->format('M j, Y g:i A'),
            'issuer_name' => $invoice->issuer->name,
            'patient' => [
                'id' => $invoice->patient->id,
                'full_name' => "{$invoice->patient->first_name} {$invoice->patient->last_name}",
                'patient_number' => $invoice->patient->patient_number,
            ],
            'treatment_id' => $invoice->treatment_id,
            'subtotal_cents' => $invoice->subtotal_cents,
            'subtotal_formatted' => $this->formatCents($invoice->subtotal_cents),
            'discount_cents' => $invoice->discount_cents,
            'discount_formatted' => $this->formatCents($invoice->discount_cents),
            'tax_cents' => $invoice->tax_cents,
            'tax_formatted' => $this->formatCents($invoice->tax_cents),
            'total_cents' => $invoice->total_cents,
            'total_formatted' => $this->formatCents($invoice->total_cents),
            'amount_paid_cents' => $invoice->amount_paid_cents,
            'amount_paid_formatted' => $this->formatCents($invoice->amount_paid_cents),
            'balance_cents' => $invoice->balance_cents,
            'balance_formatted' => $this->formatCents($invoice->balance_cents),
            'items' => $invoice->items->map(fn (InvoiceItem $item) => [
                'id' => $item->id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price_cents' => $item->unit_price_cents,
                'unit_price_formatted' => $this->formatCents($item->unit_price_cents),
                'tax_cents' => $item->tax_cents,
                'tax_formatted' => $this->formatCents($item->tax_cents),
                'line_total_cents' => $item->line_total_cents,
                'line_total_formatted' => $this->formatCents($item->line_total_cents),
            ])->values(),
            'payments' => $invoice->payments->sortByDesc('created_at')->values()->map(function (Payment $payment) use ($refundedByOriginal) {
                $refundedCents = (int) ($refundedByOriginal[$payment->payment_number] ?? 0);
                $remainingRefundableCents = $payment->status === PaymentStatus::Completed
                    ? max(0, $payment->amount_cents - $refundedCents)
                    : 0;

                return [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'amount_cents' => $payment->amount_cents,
                    'amount_formatted' => $this->formatCents($payment->amount_cents),
                    'remaining_refundable_cents' => $remainingRefundableCents,
                    'remaining_refundable_formatted' => $this->formatCents($remainingRefundableCents),
                    'method' => $payment->method->value,
                    'method_label' => ucfirst(str_replace('_', ' ', $payment->method->value)),
                    'status' => $payment->status->value,
                    'status_label' => ucfirst(str_replace('_', ' ', $payment->status->value)),
                    'paid_at_formatted' => $payment->paid_at?->format('M j, Y g:i A'),
                    'received_by_name' => $payment->receiver->name,
                    'reference_number' => $payment->reference_number,
                    'receipt_id' => $payment->receipt?->id,
                    'receipt_number' => $payment->receipt?->receipt_number,
                    'mobile_money' => $payment->mobileMoneyTransaction ? [
                        'provider' => $payment->mobileMoneyTransaction->provider->value,
                        'payer_phone' => $payment->mobileMoneyTransaction->payer_phone,
                        'transaction_id' => $payment->mobileMoneyTransaction->transaction_id,
                        'verification_status' => $payment->mobileMoneyTransaction->verification_status->value,
                    ] : null,
                ];
            })->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function receiptDetail(Receipt $receipt): array
    {
        $payment = $receipt->payment;
        $invoice = $payment->invoice;

        return [
            'receipt_number' => $receipt->receipt_number,
            'issued_at_formatted' => $receipt->created_at?->format('M j, Y g:i A'),
            'payment_number' => $payment->payment_number,
            'amount_cents' => $payment->amount_cents,
            'amount_formatted' => $this->formatCents($payment->amount_cents),
            'method' => $payment->method->value,
            'method_label' => ucfirst(str_replace('_', ' ', $payment->method->value)),
            'received_by_name' => $payment->receiver->name,
            'reference_number' => $payment->reference_number,
            'invoice_number' => $invoice->invoice_number,
            'patient_name' => "{$invoice->patient->first_name} {$invoice->patient->last_name}",
            'patient_number' => $invoice->patient->patient_number,
            'items' => $invoice->items->map(fn (InvoiceItem $item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'line_total_formatted' => $this->formatCents($item->line_total_cents),
            ])->values(),
            'invoice_total_formatted' => $this->formatCents($invoice->total_cents),
            'mobile_money' => $payment->mobileMoneyTransaction ? [
                'provider' => $payment->mobileMoneyTransaction->provider->value,
                'payer_phone' => $payment->mobileMoneyTransaction->payer_phone,
                'transaction_id' => $payment->mobileMoneyTransaction->transaction_id,
            ] : null,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function paymentMethodOptions(): array
    {
        return [
            ['value' => 'cash', 'label' => 'Cash'],
            ['value' => 'card', 'label' => 'Card'],
            ['value' => 'bank_transfer', 'label' => 'Bank Transfer'],
            ['value' => 'zaad', 'label' => 'ZAAD'],
            ['value' => 'sahal', 'label' => 'Sahal'],
            ['value' => 'edahab', 'label' => 'eDahab'],
            ['value' => 'mycash', 'label' => 'MyCash'],
            ['value' => 'insurance', 'label' => 'Insurance'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function mobileMoneyProviderOptions(): array
    {
        return [
            ['value' => 'Telesom', 'label' => 'Telesom'],
            ['value' => 'Golis', 'label' => 'Golis'],
            ['value' => 'Somtel', 'label' => 'Somtel'],
            ['value' => 'Somlink', 'label' => 'Somlink'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function verificationStatusOptions(): array
    {
        return [
            ['value' => 'verification_required', 'label' => 'Verification required'],
            ['value' => 'verified', 'label' => 'Verified'],
            ['value' => 'failed', 'label' => 'Failed'],
        ];
    }

    private function statusLabel(string $status): string
    {
        return ucfirst(str_replace('_', ' ', $status));
    }

    private function formatCents(int $cents): string
    {
        return '$'.number_format($cents / 100, 2);
    }
}
