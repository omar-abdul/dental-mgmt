<?php

namespace App\Models;

use Database\Factories\DailyCashClosingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $closing_date
 * @property int $system_cash_total_cents
 * @property int $counted_cash_cents
 * @property int $difference_cents
 * @property int $closed_by
 * @property Carbon $closed_at
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $closer
 */
#[Fillable([
    'closing_date',
    'counted_cash_cents',
    'closed_by',
    'closed_at',
    'notes',
    'created_by',
    'updated_by',
])]
class DailyCashClosing extends Model
{
    /** @use HasFactory<DailyCashClosingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'closing_date' => 'date',
            'system_cash_total_cents' => 'integer',
            'counted_cash_cents' => 'integer',
            'difference_cents' => 'integer',
            'closed_at' => 'datetime',
        ];
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
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
