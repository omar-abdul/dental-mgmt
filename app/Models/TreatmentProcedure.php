<?php

namespace App\Models;

use Database\Factories\TreatmentProcedureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $treatment_id
 * @property int $fee_item_id
 * @property string|null $tooth_fdi
 * @property int $quantity
 * @property int $fee_cents
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Treatment $treatment
 * @property-read FeeItem $feeItem
 */
#[Fillable([
    'treatment_id',
    'fee_item_id',
    'tooth_fdi',
    'quantity',
    'fee_cents',
    'created_by',
    'updated_by',
])]
class TreatmentProcedure extends Model
{
    /** @use HasFactory<TreatmentProcedureFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'fee_cents' => 'integer',
        ];
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    public function feeItem(): BelongsTo
    {
        return $this->belongsTo(FeeItem::class);
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
