<?php

namespace App\Http\Requests;

use App\Concerns\ConvertsDollarAmounts;
use App\Models\Expense;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    use ConvertsDollarAmounts;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Expense::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array{
     *     description: string,
     *     category: string,
     *     amount_cents: int,
     *     expense_date: string,
     *     notes: string|null,
     * }
     */
    public function expenseData(): array
    {
        return [
            'description' => (string) $this->input('description'),
            'category' => (string) $this->input('category'),
            'amount_cents' => $this->dollarsToCents($this->input('amount')),
            'expense_date' => (string) $this->input('expense_date'),
            'notes' => filled($this->input('notes')) ? (string) $this->input('notes') : null,
        ];
    }
}
