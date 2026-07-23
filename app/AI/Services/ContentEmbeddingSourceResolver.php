<?php

namespace App\AI\Services;

use App\AI\Contracts\ContentEmbeddingSource;
use App\AI\Support\Embeddings\EntryEmbeddingSource;
use App\AI\Support\Embeddings\PageEmbeddingSource;
use App\AI\Support\Embeddings\PostEmbeddingSource;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class ContentEmbeddingSourceResolver
{
    public function __construct(
        protected PostEmbeddingSource $posts,
        protected PageEmbeddingSource $pages,
        protected EntryEmbeddingSource $entries,
    ) {
    }

    public function resolve(Model $source): ContentEmbeddingSource
    {
        foreach ([$this->posts, $this->pages, $this->entries] as $adapter) {
            if ($adapter->supports($source)) {
                return $adapter;
            }
        }

        throw new RuntimeException('Unsupported embedding source: ' . $source::class);
    }
}
