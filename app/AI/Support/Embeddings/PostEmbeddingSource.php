<?php

namespace App\AI\Support\Embeddings;

use App\AI\Contracts\ContentEmbeddingSource;
use App\AI\Services\ContentEmbeddingService;
use App\Models\Post;
use Illuminate\Database\Eloquent\Model;

class PostEmbeddingSource implements ContentEmbeddingSource
{
    public function __construct(
        protected ContentEmbeddingService $service,
    ) {
    }

    public function supports(Model $source): bool
    {
        return $source instanceof Post;
    }

    public function summaries(Model $source): array
    {
        return $this->service->summaries($source);
    }
}
