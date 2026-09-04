<?php

namespace App\Models;

use App\Enums\ToothStatus;
use Database\Factories\ToothHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $patient_id
 * @property string $tooth_fdi
 * @property ToothStatus|null $previous_status
 * @property ToothStatus $new_status
 * @property list<string>|null $surfaces
 * @property string|null $notes
 * @property int|null $encounter_id
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'patient_id',
    'tooth_fdi',
    'previous_status',
    'new_status',
    'surfaces',
    'notes',
    'encounter_id',
    'recorded_by',
])]
class ToothHistory extends Model
{
    /** @use HasFactory<ToothHistoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'previous_status' => ToothStatus::class,
            'new_status' => ToothStatus::class,
            'surfaces' => 'array',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
