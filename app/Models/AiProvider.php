<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'driver',
        'capabilities',
        'credentials',
        'is_enabled',
        'is_default',
        'status',
        'last_error',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'credentials' => 'encrypted:array',
        'is_enabled' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function models(): HasMany
    {
        return $this->hasMany(AiModel::class, 'ai_provider_id');
    }
}
