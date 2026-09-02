<?php

namespace App\Http\Requests;

use App\Concerns\ConvertsDollarAmounts;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRefundRequest extends FormRequest
{
    use ConvertsDollarAmounts;

    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        return $invoice instanceof Invoice
            && ($this->user()?->can('refund', $invoice) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $invoice = $this->route('invoice');

        return [
            'original_payment_number' => [
                'required',
                'string',
                Rule::exists(Payment::class, 'payment_number')
                    ->where('invoice_id', $invoice instanceof Invoice ? $invoice->id : null),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function amountCents(): int
    {
        return $this->dollarsToCents($this->input('amount'));
    }

    public function originalPayment(): Payment
    {
        return Payment::query()
            ->where('payment_number', $this->input('original_payment_number'))
            ->firstOrFail();
    }
}
