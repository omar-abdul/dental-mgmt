<?php

namespace App\Http\Requests;

use App\Concerns\ConvertsDollarAmounts;
use App\Models\Invoice;
use App\Models\PaymentPlan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePaymentPlanRequest extends FormRequest
{
    use ConvertsDollarAmounts;

    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        return $invoice instanceof Invoice
            && ($this->user()?->can('create', PaymentPlan::class) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'installments' => ['required', 'array', 'min:1'],
            'installments.*.amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'installments.*.due_date' => ['required', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $invoice = $this->route('invoice');

            if (! $invoice instanceof Invoice) {
                return;
            }

            $totalAllocationCents = 0;

            foreach ((array) $this->input('installments', []) as $index => $installment) {
                if (! is_array($installment)) {
                    continue;
                }

                $totalAllocationCents += $this->dollarsToCents($installment['amount'] ?? 0);
            }

            $existingAllocatedCents = PaymentPlan::activeAllocatedCentsForInvoice($invoice->id);

            if ($totalAllocationCents + $existingAllocatedCents > $invoice->balance_cents) {
                $validator->errors()->add(
                    'installments',
                    __('Installment allocations cannot exceed the remaining invoice balance.'),
                );
            }
        });
    }

    /**
     * @return list<array{amount_cents: int, due_date: string}>
     */
    public function installmentData(): array
    {
        $installments = [];

        foreach ((array) $this->input('installments', []) as $installment) {
            if (! is_array($installment)) {
                continue;
            }

            $installments[] = [
                'amount_cents' => $this->dollarsToCents($installment['amount'] ?? 0),
                'due_date' => (string) ($installment['due_date'] ?? ''),
            ];
        }

        return $installments;
    }
}
