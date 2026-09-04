<?php

namespace App\Http\Requests;

use App\Enums\InventoryMovementType;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'inventory_batch_id' => [
                Rule::requiredIf(fn (): bool => $this->input('type') === InventoryMovementType::Consumption->value),
                'nullable',
                Rule::exists(InventoryBatch::class, 'id'),
            ],
            'expiry_date' => [
                Rule::requiredIf(fn (): bool => $this->input('type') === InventoryMovementType::AdjustmentIn->value),
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'batch_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('type') !== InventoryMovementType::Consumption->value) {
                return;
            }

            $inventoryItem = $this->route('inventoryItem');

            if (! $inventoryItem instanceof InventoryItem) {
                return;
            }

            $batchId = $this->integer('inventory_batch_id');

            if ($batchId === 0) {
                return;
            }

            $batch = InventoryBatch::query()->find($batchId);

            if ($batch === null) {
                return;
            }

            if ($batch->inventory_item_id !== $inventoryItem->id) {
                $validator->errors()->add(
                    'inventory_batch_id',
                    __('The selected batch does not belong to this item.'),
                );
            }
        });
    }
}
