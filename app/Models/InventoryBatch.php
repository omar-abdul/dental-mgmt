<?php

namespace App\Models;

use Database\Factories\InventoryBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $inventory_item_id
 * @property string $batch_number
 * @property int $quantity
 * @property Carbon $expiry_date
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read InventoryItem $inventoryItem
 */
#[Fillable([
    'inventory_item_id',
    'batch_number',
    'quantity',
    'expiry_date',
    'created_by',
    'updated_by',
])]
class InventoryBatch extends Model
{
    /** @use HasFactory<InventoryBatchFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'expiry_date' => 'date',
        ];
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isExpired(): bool
    {
        $timezone = config('app.timezone');

        return now($timezone)->startOfDay()->gt(
            $this->expiry_date->timezone($timezone)->startOfDay(),
        );
    }

    public function isExpiringSoon(int $withinDays = 30): bool
    {
        if ($this->quantity === 0) {
            return false;
        }

        if ($this->isExpired()) {
            return true;
        }

        return $this->expiry_date->lte(now()->addDays($withinDays));
    }
}
