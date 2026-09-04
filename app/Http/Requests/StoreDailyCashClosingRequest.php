<?php

namespace App\Http\Requests;

use App\Concerns\ConvertsDollarAmounts;
use App\Models\DailyCashClosing;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDailyCashClosingRequest extends FormRequest
{
    use ConvertsDollarAmounts;

    public function authorize(): bool
    {
        return $this->user()?->can('create', DailyCashClosing::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'closing_date' => [
                'required',
                'date',
                Rule::unique(DailyCashClosing::class, 'closing_date'),
            ],
            'counted_cash' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function closingDate(): string
    {
        return (string) $this->input('closing_date');
    }

    public function countedCashCents(): int
    {
        return $this->dollarsToCents($this->input('counted_cash'));
    }

    public function notes(): ?string
    {
        return filled($this->input('notes')) ? (string) $this->input('notes') : null;
    }
}
