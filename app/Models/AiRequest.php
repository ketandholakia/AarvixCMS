<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_uuid',
        'user_id',
        'feature',
        'status',
        'provider',
        'model',
        'prompt_key',
        'scope',
        'request_metadata',
        'response_metadata',
        'request_payload',
        'response_payload',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost',
        'latency_ms',
        'error_class',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'scope' => 'array',
        'request_metadata' => 'array',
        'response_metadata' => 'array',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'estimated_cost' => 'decimal:8',
        'latency_ms' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
