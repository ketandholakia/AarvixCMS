<?php

namespace App\AI\Services;

use App\AI\Contracts\VectorStore;

class VectorStoreBenchmarkService
{
    /**
     * @param array<int, array{
     *     id: string,
     *     vector: array<int, float|int>,
     *     metadata?: array<string, mixed>,
     *     text?: string
     * }> $records
     * @param array<int, array{
     *     vector: array<int, float|int>,
     *     filters?: array<string, mixed>,
     *     limit?: int
     * }> $queries
     * @return array<string, mixed>
     */
    public function benchmark(
        VectorStore $store,
        string $collection,
        array $records,
        array $queries,
    ): array {
        $indexStarted = hrtime(true);
        $store->upsert($collection, $records);
        $indexMs = (hrtime(true) - $indexStarted) / 1_000_000;

        $searchStarted = hrtime(true);
        $searchResults = [];
        $filteredResults = [];

        foreach ($queries as $query) {
            $vector = $query['vector'];
            $filters = $query['filters'] ?? [];
            $limit = (int) ($query['limit'] ?? 5);

            $searchResults[] = $store->search($collection, $vector, [], $limit);
            $filteredResults[] = $store->search($collection, $vector, $filters, $limit);
        }

        $searchMs = (hrtime(true) - $searchStarted) / 1_000_000;

        return [
            'store' => $store->name(),
            'collection' => $collection,
            'supports_metadata_filters' => $store->supportsMetadataFilters(),
            'indexed_records' => count($records),
            'queries' => count($queries),
            'index_ms' => round($indexMs, 2),
            'search_ms' => round($searchMs, 2),
            'avg_search_ms' => $queries !== [] ? round($searchMs / count($queries), 2) : 0.0,
            'indexed_per_second' => $indexMs > 0 ? round((count($records) / $indexMs) * 1000, 2) : 0.0,
            'queries_per_second' => $searchMs > 0 ? round((count($queries) / $searchMs) * 1000, 2) : 0.0,
            'search_results' => $searchResults,
            'filtered_results' => $filteredResults,
        ];
    }
}
