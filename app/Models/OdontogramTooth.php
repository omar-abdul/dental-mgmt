<?php

namespace App\Models;

use App\Enums\ToothStatus;
use Database\Factories\OdontogramToothFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $patient_id
 * @property string $tooth_fdi
 * @property ToothStatus $status
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'patient_id',
    'tooth_fdi',
    'status',
    'created_by',
    'updated_by',
])]
class OdontogramTooth extends Model
{
    /** @use HasFactory<OdontogramToothFactory> */
    use HasFactory;

    protected $table = 'odontogram_teeth';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ToothStatus::class,
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function surfaces(): HasMany
    {
        return $this->hasMany(OdontogramSurface::class);
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
