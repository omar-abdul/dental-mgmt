<?php

namespace App\Models;

use Database\Factories\ImagingResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $imaging_order_id
 * @property string|null $findings
 * @property string|null $impression
 * @property Carbon|null $reported_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'imaging_order_id',
    'findings',
    'impression',
    'reported_at',
    'created_by',
    'updated_by',
])]
class ImagingResult extends Model
{
    /** @use HasFactory<ImagingResultFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
        ];
    }

    public function imagingOrder(): BelongsTo
    {
        return $this->belongsTo(ImagingOrder::class);
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
