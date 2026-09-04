<?php

namespace App\Http\Requests;

use App\Concerns\ConvertsDollarAmounts;
use App\Enums\MobileMoneyProvider;
use App\Models\MobileMoneyReconciliation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMobileMoneyReconciliationRequest extends FormRequest
{
    use ConvertsDollarAmounts;

    public function authorize(): bool
    {
        return $this->user()?->can('create', MobileMoneyReconciliation::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reconciliation_date' => ['required', 'date'],
            'provider' => [
                'required',
                Rule::enum(MobileMoneyProvider::class),
                Rule::unique(MobileMoneyReconciliation::class)
                    ->where('reconciliation_date', $this->input('reconciliation_date')),
            ],
            'provider_total' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function reconciliationDate(): string
    {
        return (string) $this->input('reconciliation_date');
    }

    public function provider(): MobileMoneyProvider
    {
        return $this->enum('provider', MobileMoneyProvider::class);
    }

    public function providerTotalCents(): int
    {
        return $this->dollarsToCents($this->input('provider_total'));
    }

    public function notes(): ?string
    {
        return filled($this->input('notes')) ? (string) $this->input('notes') : null;
    }
}
