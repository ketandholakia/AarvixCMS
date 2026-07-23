<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class AiWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_uuid',
        'key',
        'name',
        'trigger',
        'version',
        'status',
        'conditions',
        'steps',
        'owner_user_id',
    ];

    protected $casts = [
        'version' => 'integer',
        'conditions' => 'array',
        'steps' => 'array',
        'owner_user_id' => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AiWorkflowRun::class, 'workflow_id');
    }
}
