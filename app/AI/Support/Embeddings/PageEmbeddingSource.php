<?php

namespace App\AI\Support\Embeddings;

use App\AI\Contracts\ContentEmbeddingSource;
use App\AI\Services\ContentEmbeddingService;
use App\Models\Page;
use Illuminate\Database\Eloquent\Model;

class PageEmbeddingSource implements ContentEmbeddingSource
{
    public function __construct(
        protected ContentEmbeddingService $service,
    ) {
    }

    public function supports(Model $source): bool
    {
        return $source instanceof Page;
    }

    public function summaries(Model $source): array
    {
        return $this->service->summaries($source);
    }
}
