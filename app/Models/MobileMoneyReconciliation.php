<?php

namespace App\Models;

use App\Enums\MobileMoneyProvider;
use App\Enums\ReconciliationStatus;
use Database\Factories\MobileMoneyReconciliationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $reconciliation_date
 * @property MobileMoneyProvider $provider
 * @property int $transaction_count
 * @property int $system_total_cents
 * @property int $provider_total_cents
 * @property int $difference_cents
 * @property int $reconciled_by
 * @property Carbon $reconciled_at
 * @property ReconciliationStatus $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $reconciler
 */
#[Fillable([
    'reconciliation_date',
    'provider',
    'provider_total_cents',
    'reconciled_by',
    'reconciled_at',
    'status',
    'notes',
    'created_by',
    'updated_by',
])]
class MobileMoneyReconciliation extends Model
{
    /** @use HasFactory<MobileMoneyReconciliationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reconciliation_date' => 'date',
            'provider' => MobileMoneyProvider::class,
            'transaction_count' => 'integer',
            'system_total_cents' => 'integer',
            'provider_total_cents' => 'integer',
            'difference_cents' => 'integer',
            'reconciled_at' => 'datetime',
            'status' => ReconciliationStatus::class,
        ];
    }

    public function reconciler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
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
