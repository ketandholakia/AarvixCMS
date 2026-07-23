<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class AiChatRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'retry_of_id',
        'request_uuid',
        'mode',
        'status',
        'question',
        'options',
        'context',
        'response_text',
        'response_metadata',
        'error_class',
        'error_message',
        'started_at',
        'completed_at',
        'cancelled_at',
        'cancelled_by_user_id',
    ];

    protected $casts = [
        'conversation_id' => 'integer',
        'retry_of_id' => 'integer',
        'options' => 'array',
        'context' => 'array',
        'response_metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'cancelled_by_user_id' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    public function retryOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'retry_of_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }
}
