<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiImageAsset extends Model
{
    protected $fillable = [
        'media_id',
        'source_media_id',
        'ai_request_id',
        'provider',
        'model',
        'operation',
        'prompt_hash',
        'resolution',
        'seed',
        'estimated_cost',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'estimated_cost' => 'decimal:8',
        'seed' => 'integer',
    ];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function sourceMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'source_media_id');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(AiRequest::class, 'ai_request_id');
    }
}
