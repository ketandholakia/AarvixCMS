<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'citations',
        'usage',
        'tool_calls',
        'moderation_state',
        'provider_request_id',
        'request_uuid',
        'message_order',
    ];

    protected $casts = [
        'conversation_id' => 'integer',
        'citations' => 'array',
        'usage' => 'array',
        'tool_calls' => 'array',
        'message_order' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }
}
