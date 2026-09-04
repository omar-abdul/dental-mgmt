<?php

namespace App\Models;

use App\Enums\LabOrderStatus;
use Database\Factories\LabOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $number
 * @property int $patient_id
 * @property int $dentist_id
 * @property int|null $treatment_id
 * @property int|null $encounter_id
 * @property string $description
 * @property string|null $notes
 * @property Carbon|null $due_date
 * @property LabOrderStatus $status
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'number',
    'patient_id',
    'dentist_id',
    'treatment_id',
    'encounter_id',
    'description',
    'notes',
    'due_date',
    'status',
    'created_by',
    'updated_by',
])]
class LabOrder extends Model
{
    /** @use HasFactory<LabOrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'status' => LabOrderStatus::class,
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentist(): BelongsTo
    {
        return $this->belongsTo(Dentist::class);
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
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
