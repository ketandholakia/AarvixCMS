<?php

namespace App\AI\Support\VectorStores;

use App\AI\Contracts\VectorStore;
use InvalidArgumentException;

class InMemoryVectorStore implements VectorStore
{
    /**
     * @var array<string, array<string, array{vector: array<int, float>, metadata: array<string, mixed>, text: string}>>
     */
    protected array $collections = [];

    public function name(): string
    {
        return 'in-memory';
    }

    public function supportsMetadataFilters(): bool
    {
        return true;
    }

    public function upsert(string $collection, array $records): void
    {
        foreach ($records as $record) {
            if (! isset($record['id']) || ! is_string($record['id']) || $record['id'] === '') {
                throw new InvalidArgumentException('Vector records must include a non-empty string id.');
            }

            if (! isset($record['vector']) || ! is_array($record['vector']) || $record['vector'] === []) {
                throw new InvalidArgumentException('Vector records must include a non-empty vector.');
            }

            $vector = array_map(static fn ($value): float => (float) $value, array_values($record['vector']));
            $metadata = isset($record['metadata']) && is_array($record['metadata']) ? $record['metadata'] : [];
            $text = isset($record['text']) && is_string($record['text']) ? $record['text'] : '';

            $this->collections[$collection][$record['id']] = [
                'vector' => $vector,
                'metadata' => $metadata,
                'text' => $text,
            ];
        }
    }

    public function delete(string $collection, array $ids = []): void
    {
        if ($ids === []) {
            unset($this->collections[$collection]);

            return;
        }

        foreach ($ids as $id) {
            if (! is_string($id) || $id === '') {
                continue;
            }

            unset($this->collections[$collection][$id]);
        }
    }

    public function search(string $collection, array $vector, array $filters = [], int $limit = 5): array
    {
        $vector = array_values(array_map(static fn ($value): float => (float) $value, $vector));

        if ($vector === [] || ! isset($this->collections[$collection])) {
            return [];
        }

        $results = [];

        foreach ($this->collections[$collection] as $id => $record) {
            if (! $this->matchesFilters($record['metadata'], $filters)) {
                continue;
            }

            $results[] = [
                'id' => $id,
                'score' => $this->cosineSimilarity($vector, $record['vector']),
                'metadata' => $record['metadata'],
                'text' => $record['text'],
            ];
        }

        usort($results, static fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        return array_slice($results, 0, max(0, $limit));
    }

    protected function matchesFilters(array $metadata, array $filters): bool
    {
        foreach ($filters as $key => $expected) {
            if (! array_key_exists($key, $metadata) || $metadata[$key] !== $expected) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, float> $left
     * @param array<int, float> $right
     */
    protected function cosineSimilarity(array $left, array $right): float
    {
        $length = min(count($left), count($right));

        if ($length === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $leftMagnitude = 0.0;
        $rightMagnitude = 0.0;

        for ($i = 0; $i < $length; $i++) {
            $leftValue = (float) $left[$i];
            $rightValue = (float) $right[$i];

            $dot += $leftValue * $rightValue;
            $leftMagnitude += $leftValue ** 2;
            $rightMagnitude += $rightValue ** 2;
        }

        if ($leftMagnitude <= 0.0 || $rightMagnitude <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($leftMagnitude) * sqrt($rightMagnitude));
    }
}
