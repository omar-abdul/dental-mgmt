<?php

namespace App\Models;

use App\Enums\InventoryCategory;
use App\Enums\InventoryStockStatus;
use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property InventoryCategory $category
 * @property int $quantity
 * @property string $unit
 * @property int $reorder_level
 * @property int $unit_cost_cents
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'category',
    'quantity',
    'unit',
    'reorder_level',
    'unit_cost_cents',
    'created_by',
    'updated_by',
])]
class InventoryItem extends Model
{
    /** @use HasFactory<InventoryItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => InventoryCategory::class,
            'quantity' => 'integer',
            'reorder_level' => 'integer',
            'unit_cost_cents' => 'integer',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function stockStatus(): InventoryStockStatus
    {
        if ($this->quantity === 0) {
            return InventoryStockStatus::Out;
        }

        if ($this->quantity <= $this->reorder_level) {
            return InventoryStockStatus::Low;
        }

        return InventoryStockStatus::InStock;
    }
}
