<?php

namespace App\Models;

use App\Enums\TreatmentPlanItemAcceptance;
use Database\Factories\TreatmentPlanItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $treatment_plan_id
 * @property int|null $fee_item_id
 * @property string $description
 * @property string|null $tooth_fdi
 * @property int $fee_cents
 * @property TreatmentPlanItemAcceptance $acceptance_status
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'treatment_plan_id',
    'fee_item_id',
    'description',
    'tooth_fdi',
    'fee_cents',
    'acceptance_status',
    'created_by',
    'updated_by',
])]
class TreatmentPlanItem extends Model
{
    /** @use HasFactory<TreatmentPlanItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acceptance_status' => TreatmentPlanItemAcceptance::class,
        ];
    }

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class);
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
