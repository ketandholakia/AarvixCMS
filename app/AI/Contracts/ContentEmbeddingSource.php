<?php

namespace App\AI\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ContentEmbeddingSource
{
    public function supports(Model $source): bool;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function summaries(Model $source): array;
}
