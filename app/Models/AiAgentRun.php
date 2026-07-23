<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAgentRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'run_uuid',
        'agent_key',
        'agent_version',
        'agent_name',
        'status',
        'actor_user_id',
        'source_type',
        'source_id',
        'request_uuid',
        'prompt_key',
        'context',
        'plan',
        'steps_planned',
        'steps_completed',
        'estimated_tokens',
        'estimated_cost',
        'result',
        'error_class',
        'error_message',
        'started_at',
        'completed_at',
        'failed_at',
        'halted_at',
    ];

    protected $casts = [
        'agent_version' => 'integer',
        'actor_user_id' => 'integer',
        'source_id' => 'integer',
        'context' => 'array',
        'plan' => 'array',
        'steps_planned' => 'integer',
        'steps_completed' => 'integer',
        'estimated_tokens' => 'integer',
        'estimated_cost' => 'decimal:8',
        'result' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'halted_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(AiAgentRunStep::class, 'agent_run_id');
    }

    public function getRouteKeyName(): string
    {
        return 'run_uuid';
    }
}
