<?php

namespace App\AI\Services;

use App\AI\DTOs\AiScope;
use App\AI\Contracts\VectorStore;
use App\Models\Page;
use App\Models\Post;
use App\Models\Entry;
use App\Models\User;

class RetrievalService
{
    public function __construct(
        protected VectorStore $vectorStore,
        protected TextEmbeddingService $embeddingService,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function retrieve(AiScope $scope, string $question, int $limit = 5, array $options = []): array
    {
        $limit = max(1, $limit);
        $questionVector = $this->embeddingService->vectorize($question);
        $collection = (string) config('ai.vector_store.collection', 'content_embeddings');
        $allowedVisibilities = $this->allowedVisibilities($scope);
        $filters = $this->buildFilters($scope, $options);
        $sourceTypes = $filters['source_types'] ?? [];
        unset($filters['source_types']);
        $results = [];

        $sourceTypes = is_array($sourceTypes) && $sourceTypes !== [] ? $sourceTypes : [null];

        foreach ($sourceTypes as $sourceType) {
            foreach ($allowedVisibilities as $visibility) {
                $visibilityFilters = array_merge($filters, ['visibility' => $visibility]);

                if (is_string($sourceType) && $sourceType !== '') {
                    $visibilityFilters['source_type'] = $sourceType;
                }

                $matches = $this->vectorStore->search($collection, $questionVector, $visibilityFilters, $limit * 2);

                foreach ($matches as $match) {
                    $match['visibility'] = $visibility;
                    $results[] = $match;
                }
            }
        }

        $results = $this->dedupeById($results);
        usort($results, static fn (array $left, array $right): int => $right['score'] <=> $left['score']);
        $results = array_slice($results, 0, $limit);

        $citations = array_map(fn (array $match): array => $this->buildCitation($match), $results);

        return [
            'question' => $question,
            'question_vector' => $questionVector,
            'filters' => $filters,
            'allowed_visibilities' => $allowedVisibilities,
            'citations' => $citations,
            'context' => $this->buildContext($citations),
            'answer' => $this->buildAnswer($question, $citations),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function allowedVisibilities(AiScope $scope): array
    {
        $user = $scope->userId ? User::query()->find($scope->userId) : null;

        if ($user && $user->hasRole('Admin')) {
            return ['public', 'restricted', 'private'];
        }

        if ($user && $user->hasRole('Editor')) {
            return ['public', 'restricted'];
        }

        return ['public'];
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    protected function buildFilters(AiScope $scope, array $options): array
    {
        $filters = [];

        $sourceTypes = $options['source_types'] ?? $scope->metadata['source_types'] ?? [];
        if (is_string($sourceTypes) && $sourceTypes !== '') {
            $sourceTypes = [$sourceTypes];
        }

        if (is_array($sourceTypes) && $sourceTypes !== []) {
            $filters['source_types'] = array_values(array_filter(array_map(
                static fn ($sourceType): string => trim((string) $sourceType),
                $sourceTypes,
            )));
        }

        if (isset($options['content_type']) && is_string($options['content_type']) && $options['content_type'] !== '') {
            $filters['content_type'] = $options['content_type'];
        }

        return $filters;
    }

    /**
     * @param array<int, array<string, mixed>> $results
     * @return array<int, array<string, mixed>>
     */
    protected function dedupeById(array $results): array
    {
        $deduped = [];

        foreach ($results as $result) {
            $id = (string) ($result['id'] ?? '');

            if ($id === '' || isset($deduped[$id])) {
                continue;
            }

            $deduped[$id] = $result;
        }

        return array_values($deduped);
    }

    /**
     * @param array<string, mixed> $match
     * @return array<string, mixed>
     */
    protected function buildCitation(array $match): array
    {
        $metadata = is_array($match['metadata'] ?? null) ? $match['metadata'] : [];
        $sourceType = (string) ($metadata['source_type'] ?? '');
        $sourceId = (int) ($metadata['source_id'] ?? 0);
        $chunkIndex = (int) ($metadata['chunk_index'] ?? 0);
        $title = (string) ($metadata['title'] ?? 'Untitled');
        $slug = (string) ($metadata['slug'] ?? '');
        $score = (float) ($match['score'] ?? 0.0);
        $snippet = trim((string) ($match['text'] ?? ''));

        return [
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'title' => $title,
            'chunk_index' => $chunkIndex,
            'score' => round($score, 4),
            'snippet' => $snippet,
            'public_url' => $this->publicUrl($sourceType, $slug, $metadata),
            'admin_url' => $this->adminUrl($sourceType, $sourceId, $metadata),
            'visibility' => (string) ($metadata['visibility'] ?? $match['visibility'] ?? 'public'),
            'content_type' => $metadata['content_type'] ?? null,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $citations
     */
    protected function buildContext(array $citations): string
    {
        if ($citations === []) {
            return 'No authorized sources matched the question.';
        }

        return implode("\n\n", array_map(static function (array $citation): string {
            return sprintf(
                '[%s #%d] %s: %s',
                $citation['source_type'],
                $citation['chunk_index'],
                $citation['title'],
                $citation['snippet'] ?: 'No snippet available.'
            );
        }, $citations));
    }

    /**
     * @param array<int, array<string, mixed>> $citations
     */
    protected function buildAnswer(string $question, array $citations): string
    {
        if ($citations === []) {
            return 'I could not find any authorized sources that match "' . $question . '".';
        }

        $titles = array_map(static fn (array $citation): string => $citation['title'], $citations);

        return 'I found ' . count($citations) . ' authorized source chunk(s) for "' . $question . '": ' . implode(', ', array_slice($titles, 0, 3)) . '.';
    }

    /**
     * @param array<string, mixed> $metadata
     */
    protected function publicUrl(string $sourceType, string $slug, array $metadata): ?string
    {
        return match ($sourceType) {
            Post::class => $slug !== '' ? url('/blog/' . $slug) : null,
            Page::class => $slug !== '' ? url('/' . $slug) : null,
            Entry::class => isset($metadata['content_type'], $slug) && is_string($metadata['content_type']) && $metadata['content_type'] !== '' && $slug !== ''
                ? url('/' . $metadata['content_type'] . '/' . $slug)
                : null,
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $metadata
     */
    protected function adminUrl(string $sourceType, int $sourceId, array $metadata): ?string
    {
        return match ($sourceType) {
            Post::class => url('/admin/posts/' . $sourceId . '/edit'),
            Page::class => url('/admin/pages/' . $sourceId . '/edit'),
            Entry::class => isset($metadata['content_type']) && is_string($metadata['content_type']) && $metadata['content_type'] !== ''
                ? url('/admin/entries/' . $metadata['content_type'] . '/' . $sourceId . '/edit')
                : null,
            default => null,
        };
    }
}
