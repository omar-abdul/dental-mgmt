<?php

namespace App\Http\Requests;

use App\Concerns\ConvertsDollarAmounts;
use App\Enums\MobileMoneyProvider;
use App\Enums\PaymentMethod;
use App\Enums\VerificationStatus;
use App\Models\Invoice;
use App\Models\MobileMoneyTransaction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends FormRequest
{
    use ConvertsDollarAmounts;

    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        return $invoice instanceof Invoice
            && ($this->user()?->can('pay', $invoice) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $method = PaymentMethod::tryFrom((string) $this->input('method'));
        $isMobileMoney = $method?->isMobileMoney() ?? false;

        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference_number' => [
                Rule::requiredIf(fn (): bool => $method !== null && $method->requiresReference()),
                'nullable',
                'string',
                'max:255',
            ],
            'payer_phone' => [
                Rule::requiredIf($isMobileMoney),
                'nullable',
                'string',
                'max:50',
            ],
            'transaction_id' => [
                Rule::requiredIf($isMobileMoney),
                'nullable',
                'string',
                'max:255',
                Rule::unique(MobileMoneyTransaction::class, 'transaction_id'),
            ],
            'provider' => [
                Rule::requiredIf($isMobileMoney),
                'nullable',
                Rule::enum(MobileMoneyProvider::class),
            ],
            'verification_status' => [
                Rule::requiredIf($isMobileMoney),
                'nullable',
                Rule::enum(VerificationStatus::class),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $method = PaymentMethod::tryFrom((string) $this->input('method'));

            if ($method === PaymentMethod::Cash) {
                if ($this->filled('reference_number')) {
                    $validator->errors()->add('reference_number', __('Cash payments must not include a reference number.'));
                }
            }
        });
    }

    /**
     * @return array{
     *     amount_cents: int,
     *     method: PaymentMethod,
     *     reference_number?: string|null,
     *     payer_phone?: string|null,
     *     transaction_id?: string|null,
     *     provider?: MobileMoneyProvider|null,
     *     verification_status?: VerificationStatus|null,
     * }
     */
    public function paymentData(): array
    {
        $method = $this->enum('method', PaymentMethod::class);

        $data = [
            'amount_cents' => $this->dollarsToCents($this->input('amount')),
            'method' => $method,
            'reference_number' => $this->input('reference_number'),
        ];

        if ($method?->isMobileMoney()) {
            $data['payer_phone'] = $this->input('payer_phone');
            $data['transaction_id'] = $this->input('transaction_id');
            $data['provider'] = $this->enum('provider', MobileMoneyProvider::class);
            $data['verification_status'] = $this->enum('verification_status', VerificationStatus::class);
        }

        return $data;
    }
}
