<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiPrompt extends Model
{
    use HasFactory;

    protected $fillable = [
        'prompt_key',
        'category',
        'title',
        'description',
        'active_version_number',
        'output_schema',
        'is_enabled',
    ];

    protected $casts = [
        'active_version_number' => 'integer',
        'output_schema' => 'array',
        'is_enabled' => 'boolean',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(AiPromptVersion::class);
    }

    public function activeVersion(): HasOne
    {
        return $this->hasOne(AiPromptVersion::class)->where('version_number', $this->active_version_number);
    }
}
