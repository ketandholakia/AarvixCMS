<?php

namespace App\AI\Contracts;

interface VectorStore
{
    public function name(): string;

    public function supportsMetadataFilters(): bool;

    /**
     * @param array<int, array{
     *     id: string,
     *     vector: array<int, float|int>,
     *     metadata?: array<string, mixed>,
     *     text?: string
     * }> $records
     */
    public function upsert(string $collection, array $records): void;

    /**
     * @param array<int, string> $ids
     */
    public function delete(string $collection, array $ids = []): void;

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array{
     *     id: string,
     *     score: float,
     *     metadata: array<string, mixed>,
     *     text: string
     * }>
     */
    public function search(string $collection, array $vector, array $filters = [], int $limit = 5): array;
}
