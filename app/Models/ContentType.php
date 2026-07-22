<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasSlug;
use Database\Factories\ContentTypeFactory;

class ContentType extends Model
{
    use HasFactory, HasSlug;

    protected static function newFactory(): ContentTypeFactory
    {
        return ContentTypeFactory::new();
    }

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

    // ─── Lifecycle ────────────────────────────────────────────────────────────

    protected static function booted()
    {
        static::created(function (ContentType $contentType) {
            $slug = $contentType->slug;
            $names = ["view_{$slug}", "create_{$slug}", "edit_{$slug}", "delete_{$slug}"];

            $permissions = collect($names)->map(function ($name) {
                return \App\Models\Permission::firstOrCreate(['name' => $name]);
            });

            // Grant all to Admin role automatically
            $admin = \App\Models\Role::where('name', 'Admin')->first();
            if ($admin) {
                $admin->permissions()->syncWithoutDetaching($permissions->pluck('id'));
            }
        });

        static::deleted(function (ContentType $contentType) {
            $slug = $contentType->slug;
            $names = ["view_{$slug}", "create_{$slug}", "edit_{$slug}", "delete_{$slug}"];
            \App\Models\Permission::whereIn('name', $names)->delete();
        });
    }

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
