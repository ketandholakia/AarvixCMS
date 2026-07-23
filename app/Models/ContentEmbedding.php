<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentEmbedding extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_type',
        'source_id',
        'chunk_index',
        'chunk_hash',
        'content_text',
        'metadata',
        'vector_store',
        'vector_id',
        'visibility',
        'embedding_model',
        'chunker_version',
        'indexed_at',
    ];

    protected $casts = [
        'source_id' => 'integer',
        'chunk_index' => 'integer',
        'metadata' => 'array',
        'indexed_at' => 'datetime',
    ];

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
