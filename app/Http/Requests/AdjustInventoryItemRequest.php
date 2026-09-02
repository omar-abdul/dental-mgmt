<?php

namespace App\Http\Requests;

use App\Enums\InventoryMovementType;
use App\Models\InventoryItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $inventoryItem = $this->route('inventoryItem');

        if (! $inventoryItem instanceof InventoryItem) {
            return false;
        }

        return $this->user()?->can('adjust', $inventoryItem) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(InventoryMovementType::class)],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
