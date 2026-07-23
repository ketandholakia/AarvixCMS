<?php

namespace App\AI\Support\Embeddings;

use App\AI\Contracts\ContentEmbeddingSource;
use App\AI\Services\ContentEmbeddingService;
use App\Models\Entry;
use Illuminate\Database\Eloquent\Model;

class EntryEmbeddingSource implements ContentEmbeddingSource
{
    public function __construct(
        protected ContentEmbeddingService $service,
    ) {
    }

    public function supports(Model $source): bool
    {
        return $source instanceof Entry;
    }

    public function summarize(Model $source, int $chunkIndex = 0): array
    {
        return $this->service->summarize($source, $chunkIndex);
    }
}
