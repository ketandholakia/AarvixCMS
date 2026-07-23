<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class AiWorkflowRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'run_uuid',
        'idempotency_key',
        'trigger',
        'source_type',
        'source_id',
        'actor_user_id',
        'status',
        'payload',
        'result',
        'review_task',
        'error_class',
        'error_message',
        'started_at',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'workflow_id' => 'integer',
        'source_id' => 'integer',
        'actor_user_id' => 'integer',
        'payload' => 'array',
        'result' => 'array',
        'review_task' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(AiWorkflow::class, 'workflow_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
