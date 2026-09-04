<?php

namespace App\Models;

use App\Enums\PaymentPlanStatus;
use Database\Factories\PaymentPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $invoice_id
 * @property int $total_cents
 * @property PaymentPlanStatus $status
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Invoice $invoice
 * @property-read Collection<int, Installment> $installments
 */
#[Fillable([
    'invoice_id',
    'total_cents',
    'status',
    'created_by',
    'updated_by',
])]
class PaymentPlan extends Model
{
    /** @use HasFactory<PaymentPlanFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_cents' => 'integer',
            'status' => PaymentPlanStatus::class,
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    public static function activeAllocatedCentsForInvoice(int $invoiceId): int
    {
        return (int) Installment::query()
            ->whereHas('paymentPlan', fn ($query) => $query
                ->where('invoice_id', $invoiceId)
                ->where('status', PaymentPlanStatus::Active))
            ->sum('amount_cents');
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
