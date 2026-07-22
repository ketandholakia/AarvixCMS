<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasSlug;

class ContentType extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'name',
        'slug',
        'context',
        'icon',
        'description',
        'fields_schema',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'fields_schema' => 'array',
        'is_system'     => 'boolean',
        'is_active'     => 'boolean',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfContext($query, string $context)
    {
        return $query->where('context', $context);
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Returns the fields_schema as a collection of field definitions,
     * each guaranteed to have 'key', 'label', and 'type' keys.
     */
    public function fieldDefinitions(): \Illuminate\Support\Collection
    {
        return collect($this->fields_schema ?? [])->map(function ($field) {
            return array_merge(['key' => '', 'label' => '', 'type' => 'text', 'required' => false], $field);
        });
    }
}
