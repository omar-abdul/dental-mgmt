<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
use Database\Factories\InstallmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $payment_plan_id
 * @property int $amount_cents
 * @property Carbon $due_date
 * @property InstallmentStatus $status
 * @property Carbon|null $paid_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PaymentPlan $paymentPlan
 */
#[Fillable([
    'payment_plan_id',
    'amount_cents',
    'due_date',
    'status',
    'paid_at',
    'created_by',
    'updated_by',
])]
class Installment extends Model
{
    /** @use HasFactory<InstallmentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'due_date' => 'date',
            'status' => InstallmentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function paymentPlan(): BelongsTo
    {
        return $this->belongsTo(PaymentPlan::class);
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
