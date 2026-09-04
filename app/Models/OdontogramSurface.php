<?php

namespace App\Models;

use App\Enums\ToothSurface;
use Database\Factories\OdontogramSurfaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $odontogram_tooth_id
 * @property ToothSurface $surface
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'odontogram_tooth_id',
    'surface',
])]
class OdontogramSurface extends Model
{
    /** @use HasFactory<OdontogramSurfaceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'surface' => ToothSurface::class,
        ];
    }

    public function tooth(): BelongsTo
    {
        return $this->belongsTo(OdontogramTooth::class, 'odontogram_tooth_id');
    }
}
