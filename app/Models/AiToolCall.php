<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiToolCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'tool_id',
        'call_uuid',
        'request_uuid',
        'actor_user_id',
        'source_type',
        'source_id',
        'status',
        'approval_state',
        'approved_by_user_id',
        'approved_at',
        'input_payload',
        'result_summary',
        'error_class',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'tool_id' => 'integer',
        'source_id' => 'integer',
        'actor_user_id' => 'integer',
        'approved_by_user_id' => 'integer',
        'input_payload' => 'array',
        'result_summary' => 'array',
        'approved_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function tool(): BelongsTo
    {
        return $this->belongsTo(AiTool::class, 'tool_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
