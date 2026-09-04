<?php

namespace App\Models;

use Database\Factories\CommunicationTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $code
 * @property string $channel
 * @property string $name
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['code', 'channel', 'name', 'body'])]
class CommunicationTemplate extends Model
{
    /** @use HasFactory<CommunicationTemplateFactory> */
    use HasFactory;

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
