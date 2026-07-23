<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiEmbeddingJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_uuid',
        'source_type',
        'source_id',
        'source_hash',
        'status',
        'attempts',
        'last_error_class',
        'last_error_message',
        'payload',
        'queued_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'source_id' => 'integer',
        'attempts' => 'integer',
        'payload' => 'array',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
