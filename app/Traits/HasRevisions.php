<?php

namespace App\Traits;

use App\Models\Revision;
use Illuminate\Database\Eloquent\Model;

trait HasRevisions
{
    protected static array $revisionSnapshots = [];

    /**
     * Boot the trait to listen for model events.
     */
    public static function bootHasRevisions()
    {
        static::updating(function (Model $model) {
            static::$revisionSnapshots[spl_object_id($model)] = [
                'dirty' => $model->getDirty(),
                'original' => $model->getOriginal(),
            ];
        });

        static::updated(function (Model $model) {
            $model->saveRevision('updated');
        });

        static::deleted(function (Model $model) {
            $model->saveRevision('deleted');
        });
        
        static::created(function (Model $model) {
            $model->saveRevision('created');
        });
    }

    /**
     * Get all revisions for the model.
     */
    public function revisions()
    {
        return $this->morphMany(Revision::class, 'revisionable')->orderBy('created_at', 'desc');
    }

    /**
     * Save a revision of the model.
     */
    public function saveRevision(string $event)
    {
        $snapshot = $this->pullRevisionSnapshot();
        $dirty = $event === 'updated'
            ? ($snapshot['dirty'] ?? $this->getChanges())
            : $this->getDirty();
        $original = $snapshot['original'] ?? $this->getOriginal();

        // If it's an update but nothing changed, do not save a revision.
        if ($event === 'updated' && empty($dirty)) {
            return;
        }

        // We only want to store the attributes that changed
        $before = [];
        $after = [];

        foreach ($dirty as $key => $value) {
            if ($key === 'updated_at') {
                continue;
            }
            $before[$key] = array_key_exists($key, $original) ? $original[$key] : null;
            $after[$key] = $value;
        }
        
        // For created or deleted, we just save the whole original/dirty state appropriately
        if ($event === 'created') {
            $after = $this->getAttributes();
        } elseif ($event === 'deleted') {
            $before = $this->getAttributes();
            $after = [];
        }

        $this->revisions()->create([
            'user_id' => auth()->id(),
            'before_attributes' => $before,
            'after_attributes' => $after,
            'event' => $event,
        ]);
    }

    protected function pullRevisionSnapshot(): array
    {
        $key = spl_object_id($this);
        $snapshot = static::$revisionSnapshots[$key] ?? [];
        unset(static::$revisionSnapshots[$key]);

        return is_array($snapshot) ? $snapshot : [];
    }
}
