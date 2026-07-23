<?php

namespace App\AI\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ContentEmbeddingSource
{
    public function supports(Model $source): bool;

    public function summarize(Model $source, int $chunkIndex = 0): array;
}
