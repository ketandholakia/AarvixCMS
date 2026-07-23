<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPromptVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_prompt_id',
        'version_number',
        'system_template',
        'user_template',
        'variables',
        'output_schema',
        'change_summary',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'variables' => 'array',
        'output_schema' => 'array',
    ];

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(AiPrompt::class, 'ai_prompt_id');
    }
}
