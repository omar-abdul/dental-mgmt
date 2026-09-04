<?php

namespace App\Models;

use App\Enums\InsuranceClaimStatus;
use Database\Factories\InsuranceClaimFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $invoice_id
 * @property string $provider
 * @property InsuranceClaimStatus $status
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Invoice $invoice
 */
#[Fillable([
    'invoice_id',
    'provider',
    'status',
    'created_by',
    'updated_by',
])]
class InsuranceClaim extends Model
{
    /** @use HasFactory<InsuranceClaimFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InsuranceClaimStatus::class,
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
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
