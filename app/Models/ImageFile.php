<?php

namespace App\Models;

use Database\Factories\ImageFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $imaging_order_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string|null $mime_type
 * @property int|null $size_bytes
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'imaging_order_id',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size_bytes',
    'created_by',
])]
class ImageFile extends Model
{
    /** @use HasFactory<ImageFileFactory> */
    use HasFactory;

    public function imagingOrder(): BelongsTo
    {
        return $this->belongsTo(ImagingOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
