<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_provider_id',
        'name',
        'capability',
        'context_window',
        'max_output_tokens',
        'prompt_token_cost',
        'completion_token_cost',
        'is_enabled',
        'metadata',
    ];

    protected $casts = [
        'context_window' => 'integer',
        'max_output_tokens' => 'integer',
        'prompt_token_cost' => 'decimal:8',
        'completion_token_cost' => 'decimal:8',
        'is_enabled' => 'boolean',
        'metadata' => 'array',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }
}
