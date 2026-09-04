<?php

namespace App\Models;

use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $description
 * @property string $category
 * @property int $amount_cents
 * @property Carbon $expense_date
 * @property int $recorded_by
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $recorder
 */
#[Fillable([
    'description',
    'category',
    'amount_cents',
    'expense_date',
    'recorded_by',
    'notes',
    'created_by',
    'updated_by',
])]
class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'expense_date' => 'date',
        ];
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
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
