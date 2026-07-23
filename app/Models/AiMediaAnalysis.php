<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMediaAnalysis extends Model
{
    protected $fillable = [
        'media_id',
        'ai_request_id',
        'analysis_type',
        'provider',
        'model',
        'summary',
        'alt_text',
        'caption',
        'tags',
        'ocr_text',
        'structured_data',
        'prompt_hash',
        'estimated_cost',
        'analyzed_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'structured_data' => 'array',
        'estimated_cost' => 'decimal:8',
        'analyzed_at' => 'datetime',
    ];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(AiRequest::class, 'ai_request_id');
    }
}
