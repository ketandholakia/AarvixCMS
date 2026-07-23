<?php

namespace App\AI\Services;

use App\AI\Contracts\VectorStore;
use App\AI\DTOs\AiScope;
use InvalidArgumentException;

class RagEvaluationService
{
    public function __construct(
        protected RetrievalService $retrievalService,
        protected VectorStore $vectorStore,
        protected TextEmbeddingService $embeddingService,
    ) {
    }

    /**
     * @param array<string, mixed> $fixture
     * @return array<string, mixed>
     */
    public function evaluate(array $fixture): array
    {
        $version = (string) ($fixture['version'] ?? 'unknown');
        $collection = (string) ($fixture['collection'] ?? config('ai.vector_store.collection', 'content_embeddings'));
        $cases = $fixture['cases'] ?? null;

        if (! is_array($cases) || $cases === []) {
            throw new InvalidArgumentException('RAG evaluation fixture must define a non-empty cases array.');
        }

        $this->vectorStore->delete($collection);

        $caseResults = [];
        $recallHits = 0;
        $recallExpected = 0;
        $citationCorrectHits = 0;
        $citationCorrectExpected = 0;
        $injectionSafeCases = 0;

        foreach ($cases as $case) {
            if (! is_array($case)) {
                throw new InvalidArgumentException('Each RAG evaluation case must be an array.');
            }

            $records = $this->prepareRecords($case['records'] ?? []);
            $question = (string) ($case['question'] ?? '');
            $scope = $this->prepareScope($case['scope'] ?? []);
            $options = is_array($case['options'] ?? null) ? $case['options'] : [];
            $expectedTitles = array_values(array_filter(array_map('strval', $case['expected_titles'] ?? [])));
            $forbiddenTitles = array_values(array_filter(array_map('strval', $case['forbidden_titles'] ?? [])));
            $limit = max(1, (int) ($case['limit'] ?? 5));
            $injectionProbe = (string) ($case['injection_probe'] ?? '');

            $this->vectorStore->upsert($collection, $records);
            $result = $this->retrievalService->retrieve($scope, $question, $limit, $options);
            $titles = array_values(array_filter(array_map(
                static fn (array $citation): string => (string) ($citation['title'] ?? ''),
                $result['citations'] ?? [],
            )));

            $hits = array_intersect($expectedTitles, $titles);
            $falsePositives = array_intersect($forbiddenTitles, $titles);

            $recallExpected += count($expectedTitles);
            $recallHits += count($hits);
            $citationCorrectExpected += max(1, count($expectedTitles));
            $citationCorrectHits += $falsePositives === [] ? 1 : 0;

            $injectionSafe = $injectionProbe === '' || ! str_contains($result['answer'] ?? '', $injectionProbe);
            $injectionSafeCases += $injectionSafe ? 1 : 0;

            $caseResults[] = [
                'name' => (string) ($case['name'] ?? 'unnamed'),
                'question' => $question,
                'retrieved_titles' => $titles,
                'expected_titles' => $expectedTitles,
                'forbidden_titles' => $forbiddenTitles,
                'recall' => count($expectedTitles) > 0 ? count($hits) / count($expectedTitles) : 1.0,
                'citation_correct' => $falsePositives === [],
                'injection_safe' => $injectionSafe,
            ];
        }

        return [
            'version' => $version,
            'collection' => $collection,
            'case_count' => count($cases),
            'recall' => $recallExpected > 0 ? $recallHits / $recallExpected : 1.0,
            'citation_correctness' => $citationCorrectExpected > 0 ? $citationCorrectHits / $citationCorrectExpected : 1.0,
            'injection_safety' => count($cases) > 0 ? $injectionSafeCases / count($cases) : 1.0,
            'cases' => $caseResults,
        ];
    }

    /**
     * @param array<int, mixed> $records
     * @return array<int, array<string, mixed>>
     */
    protected function prepareRecords(array $records): array
    {
        return array_values(array_map(function ($record): array {
            if (! is_array($record)) {
                throw new InvalidArgumentException('Each RAG evaluation record must be an array.');
            }

            $text = (string) ($record['text'] ?? '');
            $metadata = is_array($record['metadata'] ?? null) ? $record['metadata'] : [];

            if ($text === '') {
                throw new InvalidArgumentException('RAG evaluation records require non-empty text.');
            }

            $metadata = array_merge($metadata, [
                'title' => (string) ($metadata['title'] ?? $text),
                'slug' => (string) ($metadata['slug'] ?? ''),
                'source_type' => (string) ($metadata['source_type'] ?? 'Post'),
                'source_id' => (int) ($metadata['source_id'] ?? 0),
                'chunk_index' => (int) ($metadata['chunk_index'] ?? 0),
                'visibility' => (string) ($metadata['visibility'] ?? 'public'),
            ]);

            return [
                'id' => (string) ($record['id'] ?? md5($text)),
                'vector' => $this->embeddingService->vectorize($text),
                'metadata' => $metadata,
                'text' => $text,
            ];
        }, $records));
    }

    /**
     * @param array<string, mixed> $scopeData
     */
    protected function prepareScope(array $scopeData): AiScope
    {
        $metadata = is_array($scopeData['metadata'] ?? null) ? $scopeData['metadata'] : [];

        return new AiScope(
            userId: isset($scopeData['user_id']) ? (int) $scopeData['user_id'] : null,
            site: isset($scopeData['site']) ? (string) $scopeData['site'] : null,
            feature: isset($scopeData['feature']) ? (string) $scopeData['feature'] : 'chat',
            metadata: $metadata,
        );
    }
}
