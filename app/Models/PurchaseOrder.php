<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $number
 * @property int $supplier_id
 * @property PurchaseOrderStatus $status
 * @property string|null $notes
 * @property Carbon|null $ordered_at
 * @property Carbon|null $received_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Supplier $supplier
 * @property-read Collection<int, PurchaseOrderItem> $items
 */
#[Fillable([
    'number',
    'supplier_id',
    'status',
    'notes',
    'ordered_at',
    'received_at',
    'created_by',
    'updated_by',
])]
class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'ordered_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
