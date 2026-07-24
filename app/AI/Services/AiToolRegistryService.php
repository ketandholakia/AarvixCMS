<?php

namespace App\AI\Services;

use App\AI\DTOs\AiScope;
use App\AI\DTOs\AiToolDefinition;
use App\AI\Exceptions\AiToolAuthorizationException;
use App\AI\Exceptions\AiToolExecutionException;
use App\Models\AiTool;
use App\Models\AiToolCall;
use App\Models\Media;
use App\Models\Entry;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AiToolRegistryService
{
    /**
     * @var array<int, class-string<Model>>
     */
    protected const ALLOWED_SOURCE_MODELS = [
        Post::class,
        Page::class,
        Entry::class,
    ];

    public function __construct(
        protected RetrievalService $retrievalService,
        protected ContentEmbeddingSourceResolver $contentEmbeddingSourceResolver,
    ) {
    }

    /**
     * @param array<int, AiToolDefinition|array<string, mixed>> $definitions
     * @return Collection<int, AiTool>
     */
    public function syncDefinitions(array $definitions): Collection
    {
        return collect($definitions)->map(function (AiToolDefinition|array $definition): AiTool {
            $definition = $definition instanceof AiToolDefinition
                ? $definition
                : new AiToolDefinition(
                    key: (string) ($definition['key'] ?? ''),
                    version: (int) ($definition['version'] ?? 1),
                    name: (string) ($definition['name'] ?? ''),
                    description: $definition['description'] ?? null,
                    category: $definition['category'] ?? null,
                    handler: $definition['handler'] ?? null,
                    requiredPermission: $definition['required_permission'] ?? null,
                    confirmationPolicy: (string) ($definition['confirmation_policy'] ?? 'never'),
                    riskClassification: (string) ($definition['risk_classification'] ?? 'read'),
                    inputSchema: is_array($definition['input_schema'] ?? null) ? $definition['input_schema'] : [],
                    outputSchema: is_array($definition['output_schema'] ?? null) ? $definition['output_schema'] : [],
                    configuration: is_array($definition['configuration'] ?? null) ? $definition['configuration'] : [],
                    timeoutSeconds: (int) ($definition['timeout_seconds'] ?? 30),
                    rateLimitPerMinute: isset($definition['rate_limit_per_minute']) ? (int) $definition['rate_limit_per_minute'] : null,
                    auditRedactionPolicy: (string) ($definition['audit_redaction_policy'] ?? 'minimal'),
                    isEnabled: (bool) ($definition['is_enabled'] ?? true),
                );

            return AiTool::query()->updateOrCreate(
                ['key' => $definition->key],
                array_merge($definition->toArray(), [
                    'tool_uuid' => AiTool::query()->where('key', $definition->key)->value('tool_uuid') ?? (string) Str::uuid(),
                ])
            );
        })->values();
    }

    /**
     * @return Collection<int, AiTool>
     */
    public function all(bool $enabledOnly = true): Collection
    {
        $query = AiTool::query()->orderBy('category')->orderBy('name');

        if ($enabledOnly) {
            $query->where('is_enabled', true);
        }

        return $query->get();
    }

    public function find(string $key): ?AiTool
    {
        return AiTool::query()->where('key', $key)->first();
    }

    public function authorize(AiTool $tool, ?User $actor = null): void
    {
        if (! $tool->is_enabled) {
            throw new AiToolAuthorizationException("AI tool [{$tool->key}] is disabled.");
        }

        if ($tool->required_permission === null || $tool->required_permission === '') {
            return;
        }

        if (! $actor || ! $actor->hasPermission($tool->required_permission)) {
            throw new AiToolAuthorizationException(
                "You do not have permission to use AI tool [{$tool->key}]."
            );
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $context
     */
    public function recordCall(
        AiTool $tool,
        array $input = [],
        ?User $actor = null,
        array $context = [],
        ?Model $source = null,
    ): AiToolCall {
        $this->authorize($tool, $actor);

        return $tool->calls()->create([
            'call_uuid' => (string) Str::uuid(),
            'request_uuid' => is_string($context['request_uuid'] ?? null) ? $context['request_uuid'] : null,
            'actor_user_id' => $actor?->id,
            'source_type' => $source?->getMorphClass() ?? ($context['source_type'] ?? null),
            'source_id' => $source?->getKey() ?? ($context['source_id'] ?? null),
            'status' => $tool->confirmation_policy === 'never' ? 'pending' : 'awaiting_approval',
            'approval_state' => $tool->confirmation_policy === 'never' ? 'not_required' : 'pending',
            'approved_by_user_id' => null,
            'approved_at' => null,
            'input_payload' => array_merge($input, [
                'context' => $context,
            ]),
            'result_summary' => null,
        ]);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function execute(string $toolKey, array $input = [], ?User $actor = null, array $context = [], ?Model $source = null): array
    {
        $tool = $this->find($toolKey);

        if (! $tool) {
            throw new AiToolExecutionException("AI tool [{$toolKey}] is not registered.");
        }

        $call = $this->recordCall($tool, $input, $actor, $context, $source);

        if ($tool->confirmation_policy !== 'never') {
            return [
                'status' => 'approval_required',
                'tool_key' => $tool->key,
                'call_uuid' => $call->call_uuid,
                'call_id' => $call->id,
                'approval_state' => $call->approval_state,
            ];
        }

        try {
            $result = $this->runToolExecutor($tool, $input, $actor, $context, $source);

            $call->forceFill([
                'status' => 'succeeded',
                'result_summary' => $this->summarizeResult($result),
                'completed_at' => now(),
            ])->save();

            return $result;
        } catch (\Throwable $throwable) {
            $call->forceFill([
                'status' => 'failed',
                'error_class' => $throwable::class,
                'error_message' => $throwable->getMessage(),
                'completed_at' => now(),
            ])->save();

            throw $throwable;
        }
    }

    public function approveCall(AiToolCall $call, ?User $approver = null): array
    {
        $call->loadMissing('tool', 'actor');
        $tool = $call->tool;

        if ($call->approval_state !== 'pending' || $call->status !== 'awaiting_approval') {
            throw new AiToolExecutionException("AI tool call [{$call->call_uuid}] is not awaiting approval.");
        }

        $this->authorizeApproval($tool, $approver);

        $call->forceFill([
            'approval_state' => 'approved',
            'approved_by_user_id' => $approver?->id,
            'approved_at' => now(),
            'status' => 'pending',
        ])->save();

        $payload = is_array($call->input_payload ?? null) ? $call->input_payload : [];
        $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];
        unset($payload['context']);

        $source = $this->resolveSourceModel($call->source_type, $call->source_id);
        $executionActor = $call->actor ?? $approver;

        try {
            $result = $this->runToolExecutor($tool, $payload, $executionActor, $context, $source);

            $call->forceFill([
                'status' => 'succeeded',
                'result_summary' => $this->summarizeResult($result),
                'completed_at' => now(),
            ])->save();

            return $result;
        } catch (\Throwable $throwable) {
            $call->forceFill([
                'status' => 'failed',
                'error_class' => $throwable::class,
                'error_message' => $throwable->getMessage(),
                'completed_at' => now(),
            ])->save();

            throw $throwable;
        }
    }

    public function rejectCall(AiToolCall $call, ?User $approver = null, ?string $reason = null): AiToolCall
    {
        $call->loadMissing('tool');
        $this->authorizeApproval($call->tool, $approver);

        $call->forceFill([
            'approval_state' => 'rejected',
            'status' => 'rejected',
            'approved_by_user_id' => $approver?->id,
            'approved_at' => now(),
            'error_class' => $reason !== null && $reason !== '' ? 'approval_rejected' : null,
            'error_message' => $reason,
            'completed_at' => now(),
        ])->save();

        return $call;
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function runToolExecutor(AiTool $tool, array $input, ?User $actor, array $context, ?Model $source): array
    {
        return match ($tool->key) {
            'content.search' => $this->executeContentSearch($tool, $input, $actor, $context, $source),
            'content.summary' => $this->executeContentSummary($tool, $input, $actor, $context, $source),
            'media.search' => $this->executeMediaSearch($tool, $input, $actor, $context, $source),
            'content.draft' => $this->executeDraftArticle($tool, $input, $actor, $context, $source),
            'ai.report' => $this->executeToolCallReport($tool, $input, $actor, $context, $source),
            'seo.propose' => $this->executeSeoProposal($tool, $input, $actor, $context, $source),
            default => throw new AiToolExecutionException("AI tool [{$tool->key}] does not have an executor yet."),
        };
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function executeContentSearch(AiTool $tool, array $input, ?User $actor, array $context, ?Model $source): array
    {
        $query = trim((string) ($input['query'] ?? ''));

        if ($query === '') {
            throw new AiToolExecutionException('content.search requires a non-empty query.');
        }

        $limit = max(1, (int) ($input['limit'] ?? 5));
        $sourceTypes = $this->normalizeSourceTypes($input['source_types'] ?? null);
        $scope = new AiScope(
            userId: $actor?->id,
            site: is_string($context['site'] ?? null) ? $context['site'] : null,
            feature: 'tool:' . $tool->key,
            metadata: array_merge(
                is_array($context['metadata'] ?? null) ? $context['metadata'] : [],
                ['source_types' => $sourceTypes]
            ),
        );

        return $this->retrievalService->retrieve(
            $scope,
            $query,
            $limit,
            array_filter([
                'source_types' => $sourceTypes,
                'content_type' => is_string($input['content_type'] ?? null) ? $input['content_type'] : null,
            ], static fn ($value): bool => $value !== null && $value !== [])
        );
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function executeContentSummary(AiTool $tool, array $input, ?User $actor, array $context, ?Model $source): array
    {
        $source ??= $this->resolveSourceModelFromInput($input);

        if (! $source instanceof Model) {
            throw new AiToolExecutionException('content.summary requires a source model or source identifiers.');
        }

        $summaries = $this->contentEmbeddingSourceResolver->resolve($source)->summaries($source);
        $highlights = array_values(array_filter(array_map(
            static fn (array $summary): string => trim((string) ($summary['content_text'] ?? '')),
            $summaries,
        )));
        $combinedText = trim(implode(' ', $highlights));

        return [
            'source_type' => $source::class,
            'source_id' => $source->getKey(),
            'title' => trim((string) ($source->title ?? $source->name ?? class_basename($source))),
            'summary' => $this->summarizePlainText($combinedText),
            'highlights' => array_slice($highlights, 0, 3),
            'chunk_count' => count($summaries),
            'visibility' => $summaries[0]['visibility'] ?? null,
            'slug' => (string) ($source->slug ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function executeMediaSearch(AiTool $tool, array $input, ?User $actor, array $context, ?Model $source): array
    {
        $query = trim((string) ($input['query'] ?? ''));
        $limit = max(1, min(25, (int) ($input['limit'] ?? 10)));
        $mimeType = is_string($input['mime_type'] ?? null) ? trim($input['mime_type']) : '';
        $imagesOnly = filter_var($input['images_only'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $queryBuilder = Media::query()->latest('id');

        if ($query !== '') {
            $queryBuilder->where(function ($builder) use ($query): void {
                $builder->where('filename', 'like', '%' . $query . '%')
                    ->orWhere('original_filename', 'like', '%' . $query . '%')
                    ->orWhere('alt_text', 'like', '%' . $query . '%')
                    ->orWhere('caption', 'like', '%' . $query . '%');
            });
        }

        if ($mimeType !== '') {
            $queryBuilder->where('mime_type', 'like', $mimeType . '%');
        }

        if ($imagesOnly) {
            $queryBuilder->where('mime_type', 'like', 'image/%');
        }

        $media = $queryBuilder->limit($limit)->get();

        return [
            'query' => $query,
            'count' => $media->count(),
            'items' => $media->map(static function (Media $item): array {
                return [
                    'id' => $item->id,
                    'filename' => $item->filename,
                    'original_filename' => $item->original_filename,
                    'url' => $item->url,
                    'mime_type' => $item->mime_type,
                    'size' => $item->size,
                    'human_size' => $item->human_size,
                    'width' => $item->width,
                    'height' => $item->height,
                    'alt_text' => $item->alt_text,
                    'caption' => $item->caption,
                    'uploaded_by' => $item->uploaded_by,
                    'created_at' => optional($item->created_at)->toISOString(),
                ];
            })->values()->all(),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function executeDraftArticle(AiTool $tool, array $input, ?User $actor, array $context, ?Model $source): array
    {
        $title = trim((string) ($input['title'] ?? ''));

        if ($title === '') {
            throw new AiToolExecutionException('content.draft requires a title.');
        }

        $draft = Post::query()->create([
            'author_id' => $actor?->id,
            'category_id' => isset($input['category_id']) ? (int) $input['category_id'] : null,
            'title' => $title,
            'slug' => is_string($input['slug'] ?? null) ? trim($input['slug']) : null,
            'excerpt' => is_string($input['excerpt'] ?? null) ? trim($input['excerpt']) : null,
            'body' => is_string($input['body'] ?? null) ? trim($input['body']) : null,
            'status' => 'draft',
            'meta_title' => is_string($input['meta_title'] ?? null) ? trim($input['meta_title']) : null,
            'meta_description' => is_string($input['meta_description'] ?? null) ? trim($input['meta_description']) : null,
            'published_at' => null,
        ]);

        return [
            'source_type' => Post::class,
            'source_id' => $draft->id,
            'title' => $draft->title,
            'slug' => $draft->slug,
            'status' => $draft->status,
            'author_id' => $draft->author_id,
            'edit_url' => url('/admin/posts/' . $draft->id . '/edit'),
            'public_url' => url('/blog/' . $draft->slug),
            'warnings' => [
                'Review the draft before publishing.',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function executeToolCallReport(AiTool $tool, array $input, ?User $actor, array $context, ?Model $source): array
    {
        $query = AiToolCall::query()->with('tool')->latest('id');

        $toolKey = is_string($input['tool_key'] ?? null) ? trim($input['tool_key']) : '';
        $status = is_string($input['status'] ?? null) ? trim($input['status']) : '';
        $approvalState = is_string($input['approval_state'] ?? null) ? trim($input['approval_state']) : '';
        $format = strtolower(is_string($input['format'] ?? null) ? trim($input['format']) : 'json');
        $limit = max(1, min(100, (int) ($input['limit'] ?? 25)));

        if ($toolKey !== '') {
            $query->whereHas('tool', static fn ($builder) => $builder->where('key', $toolKey));
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($approvalState !== '') {
            $query->where('approval_state', $approvalState);
        }

        if (isset($input['started_after']) && $input['started_after'] !== '') {
            $startedAfter = Carbon::parse((string) $input['started_after']);
            $query->where('started_at', '>=', $startedAfter);
        }

        if (isset($input['started_before']) && $input['started_before'] !== '') {
            $startedBefore = Carbon::parse((string) $input['started_before']);
            $query->where('started_at', '<=', $startedBefore);
        }

        $calls = $query->limit($limit)->get();
        $summary = [
            'total_calls' => (int) AiToolCall::query()->count(),
            'matched_calls' => $calls->count(),
            'succeeded' => (int) $calls->where('status', 'succeeded')->count(),
            'failed' => (int) $calls->where('status', 'failed')->count(),
            'awaiting_approval' => (int) $calls->where('status', 'awaiting_approval')->count(),
            'approved' => (int) $calls->where('approval_state', 'approved')->count(),
            'rejected' => (int) $calls->where('approval_state', 'rejected')->count(),
            'by_tool' => $calls->groupBy(static fn (AiToolCall $call): string => (string) ($call->tool->key ?? 'unknown'))->map(static fn ($group): int => $group->count())->all(),
        ];

        $rows = $calls->map(static function (AiToolCall $call): array {
            return [
                'call_uuid' => $call->call_uuid,
                'tool_key' => $call->tool->key ?? null,
                'status' => $call->status,
                'approval_state' => $call->approval_state,
                'actor_user_id' => $call->actor_user_id,
                'source_type' => $call->source_type,
                'source_id' => $call->source_id,
                'request_uuid' => $call->request_uuid,
                'started_at' => optional($call->started_at)->toISOString(),
                'completed_at' => optional($call->completed_at)->toISOString(),
            ];
        })->values()->all();

        $csv = $this->toolCallRowsToCsv($rows);

        if ($format === 'csv') {
            return [
                'format' => 'csv',
                'filename' => 'ai-tool-calls-' . now()->format('Y-m-d-His') . '.csv',
                'content_type' => 'text/csv',
                'summary' => $summary,
                'csv' => $csv,
                'rows' => $rows,
            ];
        }

        return [
            'format' => 'json',
            'summary' => $summary,
            'rows' => $rows,
            'csv' => $csv,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function executeSeoProposal(AiTool $tool, array $input, ?User $actor, array $context, ?Model $source): array
    {
        $source ??= $this->resolveSourceModelFromInput($input);

        $title = trim((string) ($input['title'] ?? $source?->title ?? $source?->name ?? ''));
        $content = trim(implode(' ', array_values(array_filter([
            is_string($input['content'] ?? null) ? $input['content'] : null,
            is_string($source?->excerpt ?? null) ? $source->excerpt : null,
            $source instanceof Model ? $this->combinedSourceText($source) : null,
        ]))));

        return [
            'source_type' => $source?->getMorphClass() ?? ($context['source_type'] ?? null),
            'source_id' => $source?->getKey() ?? ($context['source_id'] ?? null),
            'meta_title' => $this->truncateText($title !== '' ? $title : 'Untitled', 60),
            'meta_description' => $this->truncateText($content !== '' ? $this->summarizePlainText($content) : 'Draft SEO metadata for review.', 155),
            'slug' => is_string($input['slug'] ?? null) ? trim($input['slug']) : null,
            'keywords' => array_values(array_filter(array_map(
                static fn ($keyword): string => trim((string) $keyword),
                (array) ($input['keywords'] ?? [])
            ))),
            'warnings' => [
                'Review before applying this proposal to live content.',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    protected function summarizeResult(array $result): array
    {
        return [
            'format' => $result['format'] ?? null,
            'answer' => $result['answer'] ?? null,
            'summary' => $result['summary'] ?? null,
            'meta_title' => $result['meta_title'] ?? null,
            'meta_description' => $result['meta_description'] ?? null,
            'context' => $result['context'] ?? null,
            'count' => isset($result['count']) ? (int) $result['count'] : null,
            'total_calls' => isset($result['summary']['total_calls']) ? (int) $result['summary']['total_calls'] : null,
            'matched_calls' => isset($result['summary']['matched_calls']) ? (int) $result['summary']['matched_calls'] : null,
            'citation_count' => is_array($result['citations'] ?? null) ? count($result['citations']) : 0,
            'chunk_count' => isset($result['chunk_count']) ? (int) $result['chunk_count'] : null,
            'filename' => $result['filename'] ?? null,
            'content_type' => $result['content_type'] ?? null,
            'items' => is_array($result['items'] ?? null) ? $this->summarizeMediaItems($result['items']) : [],
            'rows' => is_array($result['rows'] ?? null) ? array_values($result['rows']) : [],
            'citations' => is_array($result['citations'] ?? null)
                ? array_map(static fn (array $citation): array => Arr::only($citation, [
                    'source_type',
                    'source_id',
                    'title',
                    'chunk_index',
                    'score',
                    'public_url',
                    'accessible_url',
                    'visibility',
                    'content_type',
                ]), $result['citations'])
                : [],
            'highlights' => is_array($result['highlights'] ?? null) ? array_values($result['highlights']) : [],
            'warnings' => is_array($result['warnings'] ?? null) ? array_values($result['warnings']) : [],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    protected function summarizeMediaItems(array $items): array
    {
        return array_map(static fn (array $item): array => Arr::only($item, [
            'id',
            'filename',
            'original_filename',
            'url',
            'mime_type',
            'human_size',
            'width',
            'height',
            'alt_text',
            'caption',
            'uploaded_by',
            'created_at',
        ]), $items);
    }

    /**
     * @param array<string, mixed> $input
     */
    protected function resolveSourceModelFromInput(array $input): ?Model
    {
        $sourceType = is_string($input['source_type'] ?? null) ? trim($input['source_type']) : '';
        $sourceId = isset($input['source_id']) ? (int) $input['source_id'] : 0;

        return $this->resolveSourceModel($sourceType, $sourceId);
    }

    protected function resolveSourceModel(?string $sourceType, ?int $sourceId): ?Model
    {
        if (
            ! is_string($sourceType)
            || trim($sourceType) === ''
            || ! is_int($sourceId)
            || $sourceId <= 0
            || ! class_exists($sourceType)
            || ! in_array($sourceType, self::ALLOWED_SOURCE_MODELS, true)
        ) {
            return null;
        }

        /** @var class-string<Model> $sourceType */
        return $sourceType::query()->find($sourceId);
    }

    protected function authorizeApproval(AiTool $tool, ?User $approver = null): void
    {
        if ($approver === null) {
            throw new AiToolAuthorizationException('Tool approval requires an approver.');
        }

        if ($approver->hasRole('Admin')) {
            return;
        }

        if ($tool->required_permission === null || $tool->required_permission === '') {
            return;
        }

        if (! $approver->hasPermission($tool->required_permission)) {
            throw new AiToolAuthorizationException(
                "You do not have permission to approve AI tool [{$tool->key}]."
            );
        }
    }

    protected function combinedSourceText(Model $source): string
    {
        $summaries = $this->contentEmbeddingSourceResolver->resolve($source)->summaries($source);

        return trim(implode(' ', array_values(array_filter(array_map(
            static fn (array $summary): string => trim((string) ($summary['content_text'] ?? '')),
            $summaries,
        )))));
    }

    protected function summarizePlainText(string $text): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?: '');

        if ($text === '') {
            return '';
        }

        if (preg_match('/^(.+?[.!?])\s+/', $text, $matches)) {
            return $this->truncateText($matches[1], 240);
        }

        return $this->truncateText($text, 240);
    }

    protected function truncateText(string $text, int $limit): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?: '');

        if ($text === '' || mb_strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, max(0, $limit - 1))) . '...';
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    protected function toolCallRowsToCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return '';
        }

        $headers = [
            'call_uuid',
            'tool_key',
            'status',
            'approval_state',
            'actor_user_id',
            'source_type',
            'source_id',
            'request_uuid',
            'started_at',
            'completed_at',
        ];

        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, array_map(static fn (string $header): string => (string) ($row[$header] ?? ''), $headers));
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return is_string($csv) ? $csv : '';
    }

    /**
     * @param mixed $sourceTypes
     * @return array<int, string>
     */
    protected function normalizeSourceTypes(mixed $sourceTypes): array
    {
        if (is_string($sourceTypes) && $sourceTypes !== '') {
            return [$sourceTypes];
        }

        if (! is_array($sourceTypes)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($sourceType): string => trim((string) $sourceType),
            $sourceTypes,
        )));
    }
}
