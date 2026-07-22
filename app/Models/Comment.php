<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'parent_id',
        'user_id',
        'guest_name',
        'guest_email',
        'guest_url',
        'body',
        'status',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the parent commentable model (post or page).
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who made the comment, if they were authenticated.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent comment if this is a reply.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Get the replies to this comment.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    /**
     * Get the display name of the comment author.
     */
    public function getAuthorNameAttribute(): string
    {
        if ($this->user_id && $this->user) {
            return $this->user->name;
        }

        return $this->guest_name ?? 'Anonymous';
    }

    /**
     * Get the author's email.
     */
    public function getAuthorEmailAttribute(): string
    {
        if ($this->user_id && $this->user) {
            return $this->user->email;
        }

        return $this->guest_email ?? '';
    }
}
