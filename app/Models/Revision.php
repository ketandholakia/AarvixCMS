<?php

namespace App\Models;

use App\Models\AiRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Revision extends Model
{
    protected $fillable = [
        'revisionable_type',
        'revisionable_id',
        'user_id',
        'ai_request_id',
        'before_attributes',
        'after_attributes',
        'event',
    ];

    protected $casts = [
        'before_attributes' => 'array',
        'after_attributes' => 'array',
    ];

    /**
     * Get the parent revisionable model (post, page, etc.).
     */
    public function revisionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who made the change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aiRequest(): BelongsTo
    {
        return $this->belongsTo(AiRequest::class, 'ai_request_id');
    }
}
