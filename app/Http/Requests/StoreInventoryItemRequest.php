<?php

namespace App\Http\Requests;

use App\Enums\InventoryCategory;
use App\Models\InventoryItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', InventoryItem::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(InventoryCategory::class)],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            'reorder_level' => ['required', 'integer', 'min:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'expiry_date' => [
                Rule::requiredIf(fn (): bool => $this->integer('quantity') > 0),
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'batch_number' => ['nullable', 'string', 'max:100'],
        ];
    }
}
