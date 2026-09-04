<?php

namespace App\Http\Requests;

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PurchaseOrder::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', Rule::exists(Supplier::class, 'id')],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', Rule::exists(InventoryItem::class, 'id')],
            'items.*.quantity_ordered' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }
}
