<?php

namespace App\Models;

use App\Enums\ImagingOrderStatus;
use App\Enums\ImagingOrderType;
use Database\Factories\ImagingOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $number
 * @property int $patient_id
 * @property int $dentist_id
 * @property int|null $encounter_id
 * @property ImagingOrderType $type
 * @property string|null $notes
 * @property ImagingOrderStatus $status
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Patient $patient
 * @property-read Dentist $dentist
 * @property-read Encounter|null $encounter
 * @property-read ImagingResult|null $result
 * @property-read Collection<int, ImageFile> $files
 */
#[Fillable([
    'number',
    'patient_id',
    'dentist_id',
    'encounter_id',
    'type',
    'notes',
    'status',
    'created_by',
    'updated_by',
])]
class ImagingOrder extends Model
{
    /** @use HasFactory<ImagingOrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ImagingOrderType::class,
            'status' => ImagingOrderStatus::class,
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

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(ImagingResult::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ImageFile::class);
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
