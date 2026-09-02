<?php

namespace App\Models;

use App\Enums\MobileMoneyProvider;
use App\Enums\VerificationStatus;
use Database\Factories\MobileMoneyTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $payment_id
 * @property MobileMoneyProvider $provider
 * @property string $payer_phone
 * @property string $transaction_id
 * @property string $reference_number
 * @property VerificationStatus $verification_status
 * @property int|null $verified_by
 * @property Carbon|null $verified_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Payment $payment
 * @property-read User|null $verifier
 */
#[Fillable([
    'payment_id',
    'provider',
    'payer_phone',
    'transaction_id',
    'reference_number',
    'verification_status',
    'verified_by',
    'verified_at',
    'created_by',
    'updated_by',
])]
class MobileMoneyTransaction extends Model
{
    /** @use HasFactory<MobileMoneyTransactionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => MobileMoneyProvider::class,
            'verification_status' => VerificationStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
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
