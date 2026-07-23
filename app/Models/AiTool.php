<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiTool extends Model
{
    use HasFactory;

    protected $fillable = [
        'tool_uuid',
        'key',
        'version',
        'name',
        'description',
        'category',
        'handler',
        'required_permission',
        'confirmation_policy',
        'risk_classification',
        'input_schema',
        'output_schema',
        'configuration',
        'timeout_seconds',
        'rate_limit_per_minute',
        'audit_redaction_policy',
        'is_enabled',
    ];

    protected $casts = [
        'version' => 'integer',
        'input_schema' => 'array',
        'output_schema' => 'array',
        'configuration' => 'array',
        'timeout_seconds' => 'integer',
        'rate_limit_per_minute' => 'integer',
        'is_enabled' => 'boolean',
    ];

    public function calls(): HasMany
    {
        return $this->hasMany(AiToolCall::class, 'tool_id');
    }
}
