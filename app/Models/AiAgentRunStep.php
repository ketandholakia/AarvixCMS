<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentRunStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_run_id',
        'step_index',
        'tool_key',
        'status',
        'approval_state',
        'ai_tool_call_id',
        'input_payload',
        'result_payload',
        'estimated_tokens',
        'estimated_cost',
        'error_class',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'agent_run_id' => 'integer',
        'step_index' => 'integer',
        'ai_tool_call_id' => 'integer',
        'input_payload' => 'array',
        'result_payload' => 'array',
        'estimated_tokens' => 'integer',
        'estimated_cost' => 'decimal:8',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AiAgentRun::class, 'agent_run_id');
    }

    public function toolCall(): BelongsTo
    {
        return $this->belongsTo(AiToolCall::class, 'ai_tool_call_id');
    }
}
