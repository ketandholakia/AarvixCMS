<?php

namespace App\Services;

use App\AI\DTOs\AiRequestData;
use App\AI\Services\AiManager;
use App\AI\Services\ContentEmbeddingService;
use App\Models\AiWorkflow;
use App\Models\AiWorkflowRun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

class WorkflowService
{
    public function __construct(
        protected AiManager $aiManager,
        protected ContentEmbeddingService $contentEmbeddingService,
    ) {
    }

    public function handlePublishedContent(Model $source, ?\App\Models\User $actor = null): ?AiWorkflowRun
    {
        if ((string) ($source->status ?? '') !== 'published') {
            return null;
        }

        if (! $this->needsSeoReview($source)) {
            return null;
        }

        $workflow = $this->publishWorkflow($actor?->id);
        $idempotencyKey = $this->idempotencyKey($workflow, $source);

        $existing = AiWorkflowRun::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        $summary = $this->contentEmbeddingService->summarize($source);
        $contentText = trim((string) ($summary['content_text'] ?? ''));

        $run = AiWorkflowRun::query()->create([
            'workflow_id' => $workflow->id,
            'run_uuid' => (string) Str::uuid(),
            'idempotency_key' => $idempotencyKey,
            'trigger' => $workflow->trigger,
            'source_type' => $source::class,
            'source_id' => $source->getKey(),
            'actor_user_id' => $actor?->id,
            'status' => 'running',
            'started_at' => now(),
            'payload' => [
                'source_snapshot' => $summary,
                'missing_fields' => $this->missingSeoFields($source),
            ],
        ]);

        try {
            $result = $this->aiManager->generate(new AiRequestData(
                input: [
                    'operation' => 'seo',
                    'title' => (string) ($source->title ?? ''),
                    'content' => $contentText,
                    'prompt' => 'Generate SEO metadata for a published CMS item that is missing metadata. Return a concise SEO draft.',
                ],
                options: [
                    'source_type' => $source::class,
                    'source_id' => $source->getKey(),
                    'missing_fields' => $run->payload['missing_fields'] ?? [],
                ],
                provider: config('ai.default_provider', 'fake'),
                model: data_get(config('ai.models.writer'), 'model', 'fake-writer'),
                promptKey: 'workflow.publish.seo',
                feature: 'writer',
            ));

            $seo = is_array($result->response['seo'] ?? null) ? $result->response['seo'] : [];
            $run->forceFill([
                'status' => 'succeeded',
                'result' => $result->response,
                'review_task' => $this->buildReviewTask($source, $seo),
                'completed_at' => now(),
            ])->save();
        } catch (Throwable $e) {
            $run->forceFill([
                'status' => 'failed',
                'error_class' => $e::class,
                'error_message' => $e->getMessage(),
                'failed_at' => now(),
            ])->save();

            report($e);
        }

        return $run->fresh();
    }

    protected function publishWorkflow(?int $ownerUserId = null): AiWorkflow
    {
        return AiWorkflow::query()->firstOrCreate(
            ['key' => 'content.publish.seo-review'],
            [
                'workflow_uuid' => (string) Str::uuid(),
                'name' => 'Publish SEO review',
                'trigger' => 'content.published',
                'version' => 1,
                'status' => 'enabled',
                'conditions' => [
                    'requires_published_status' => true,
                    'requires_missing_seo_metadata' => true,
                ],
                'steps' => [
                    ['key' => 'generate_seo_draft', 'type' => 'ai.generate', 'status' => 'pending'],
                    ['key' => 'create_editor_review_task', 'type' => 'task.create', 'status' => 'pending'],
                ],
                'owner_user_id' => $ownerUserId,
            ]
        );
    }

    protected function idempotencyKey(AiWorkflow $workflow, Model $source): string
    {
        return hash('sha256', implode('|', [
            $workflow->key,
            $workflow->version,
            $source::class,
            (string) $source->getKey(),
        ]));
    }

    protected function needsSeoReview(Model $source): bool
    {
        return trim((string) ($source->meta_title ?? '')) === '' || trim((string) ($source->meta_description ?? '')) === '';
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildReviewTask(Model $source, array $seo): array
    {
        return [
            'status' => 'open',
            'assignee_role' => 'Editor',
            'title' => 'Review SEO suggestions for ' . trim((string) ($source->title ?? class_basename($source))),
            'details' => [
                'meta_title' => $seo['meta_title'] ?? null,
                'meta_description' => $seo['meta_description'] ?? null,
                'slug' => $seo['slug'] ?? null,
                'warnings' => $seo['warnings'] ?? [],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function missingSeoFields(Model $source): array
    {
        $missing = [];

        if (trim((string) ($source->meta_title ?? '')) === '') {
            $missing[] = 'meta_title';
        }

        if (trim((string) ($source->meta_description ?? '')) === '') {
            $missing[] = 'meta_description';
        }

        return $missing;
    }
}
