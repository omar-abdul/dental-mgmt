<?php

namespace App\Models;

use Database\Factories\SoapNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $encounter_id
 * @property string|null $subjective
 * @property string|null $objective
 * @property string|null $assessment
 * @property string|null $plan
 * @property Carbon|null $signed_at
 * @property int|null $signed_by
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'encounter_id',
    'subjective',
    'objective',
    'assessment',
    'plan',
    'created_by',
    'updated_by',
])]
class SoapNote extends Model
{
    /** @use HasFactory<SoapNoteFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    public function isSigned(): bool
    {
        return $this->signed_at !== null;
    }

    public function sign(User $user): void
    {
        $this->forceFill([
            'signed_at' => now(),
            'signed_by' => $user->id,
            'updated_by' => $user->id,
        ])->save();
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(SoapNoteAmendment::class);
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
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
