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
        'alt_text',
        'caption',
        'tags',
        'ocr_text',
        'prompt_hash',
        'resolution',
        'seed',
        'moderation_status',
        'moderation_reviewed_at',
        'retention_expires_at',
        'estimated_cost',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'tags' => 'array',
        'estimated_cost' => 'decimal:8',
        'seed' => 'integer',
        'moderation_reviewed_at' => 'datetime',
        'retention_expires_at' => 'datetime',
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
