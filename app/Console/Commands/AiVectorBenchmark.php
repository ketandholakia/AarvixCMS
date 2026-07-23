<?php

namespace App\Console\Commands;

use App\AI\Contracts\VectorStore;
use App\AI\Services\VectorStoreBenchmarkService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class AiVectorBenchmark extends Command
{
    protected $signature = 'ai:vector-benchmark {--collection=content_embeddings : Logical collection name to benchmark} {--records=150 : Number of synthetic records to index} {--queries=30 : Number of synthetic queries to run}';

    protected $description = 'Benchmark the configured vector-store adapter with a representative synthetic corpus.';

    public function handle(VectorStoreBenchmarkService $benchmarkService, VectorStore $vectorStore): int
    {
        $collection = (string) $this->option('collection');
        $recordCount = max(1, (int) $this->option('records'));
        $queryCount = max(1, (int) $this->option('queries'));

        $this->info('AI vector-store benchmark');
        $this->line('Store: ' . $vectorStore->name());
        $this->line('Collection: ' . $collection);

        try {
            $records = $this->buildRecords($recordCount);
            $queries = $this->buildQueries($queryCount);
            $report = $benchmarkService->benchmark($vectorStore, $collection, $records, $queries);
        } catch (Throwable $e) {
            $this->error('Vector benchmark failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->table(['Metric', 'Value'], [
            ['Indexed records', (string) $report['indexed_records']],
            ['Queries', (string) $report['queries']],
            ['Supports metadata filters', $report['supports_metadata_filters'] ? 'yes' : 'no'],
            ['Index time', $report['index_ms'] . ' ms'],
            ['Search time', $report['search_ms'] . ' ms'],
            ['Avg search', $report['avg_search_ms'] . ' ms'],
            ['Index throughput', $report['indexed_per_second'] . ' rec/s'],
            ['Query throughput', $report['queries_per_second'] . ' qps'],
        ]);

        $this->line('');
        $this->line('Benchmark completed successfully.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{
     *     id: string,
     *     vector: array<int, float>,
     *     metadata: array<string, mixed>,
     *     text: string
     * }>
     */
    protected function buildRecords(int $count): array
    {
        $categories = [
            'docs' => [1.0, 0.1, 0.0],
            'support' => [0.1, 1.0, 0.0],
            'news' => [0.0, 0.1, 1.0],
        ];

        $records = [];

        for ($i = 0; $i < $count; $i++) {
            $category = array_keys($categories)[$i % count($categories)];
            $vector = $categories[$category];
            $vector[0] += ($i % 5) * 0.01;
            $vector[1] += ($i % 7) * 0.01;
            $vector[2] += ($i % 11) * 0.01;

            $records[] = [
                'id' => 'record-' . ($i + 1),
                'vector' => $vector,
                'metadata' => [
                    'category' => $category,
                    'visibility' => $i % 4 === 0 ? 'restricted' : 'public',
                    'source_type' => $category === 'docs' ? 'Page' : ($category === 'support' ? 'Entry' : 'Post'),
                ],
                'text' => Str::headline($category) . ' corpus item ' . ($i + 1),
            ];
        }

        return $records;
    }

    /**
     * @return array<int, array{
     *     vector: array<int, float>,
     *     filters: array<string, mixed>,
     *     limit: int
     * }>
     */
    protected function buildQueries(int $count): array
    {
        $queries = [];
        $vectors = [
            ['vector' => [1.0, 0.0, 0.0], 'filters' => ['category' => 'docs']],
            ['vector' => [0.0, 1.0, 0.0], 'filters' => ['category' => 'support']],
            ['vector' => [0.0, 0.0, 1.0], 'filters' => ['category' => 'news']],
        ];

        for ($i = 0; $i < $count; $i++) {
            $template = $vectors[$i % count($vectors)];
            $queries[] = [
                'vector' => $template['vector'],
                'filters' => $i % 2 === 0 ? $template['filters'] : ['visibility' => 'public'],
                'limit' => 5,
            ];
        }

        return $queries;
    }
}
