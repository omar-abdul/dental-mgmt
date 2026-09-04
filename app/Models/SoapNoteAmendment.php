<?php

namespace App\Models;

use Database\Factories\SoapNoteAmendmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $soap_note_id
 * @property string $body
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'soap_note_id',
    'body',
    'created_by',
])]
class SoapNoteAmendment extends Model
{
    /** @use HasFactory<SoapNoteAmendmentFactory> */
    use HasFactory;

    public function soapNote(): BelongsTo
    {
        return $this->belongsTo(SoapNote::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
