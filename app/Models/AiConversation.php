<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiConversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'conversation_uuid',
        'user_id',
        'scope',
        'title',
        'status',
        'provider',
        'model',
        'model_settings',
        'last_message_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'scope' => 'array',
        'model_settings' => 'array',
        'last_message_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AiChatRun::class, 'conversation_id');
    }
}
