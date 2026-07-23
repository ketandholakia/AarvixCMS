<?php

namespace App\Services;

use App\AI\DTOs\AiRequestData;
use App\AI\Services\AiManager;
use App\AI\Services\ContentEmbeddingService;
use App\Models\AiWorkflow;
use App\Models\AiWorkflowRun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\User;
use Throwable;

class WorkflowService
{
    public function __construct(
        protected AiManager $aiManager,
        protected ContentEmbeddingService $contentEmbeddingService,
    ) {
    }

    public function handlePublishedContent(Model $source, ?User $actor = null): ?AiWorkflowRun
    {
        if ((string) ($source->status ?? '') !== 'published') {
            return null;
        }

        $summary = $this->contentEmbeddingService->summarize($source);
        $contentText = trim((string) ($summary['content_text'] ?? ''));

        $runs = [];

        if ($this->needsSeoReview($source)) {
            $runs[] = $this->runSeoWorkflow($source, $actor, $summary, $contentText);
        }

        $runs[] = $this->runSocialWorkflow($source, $actor, $summary, $contentText);

        foreach ($runs as $run) {
            if ($run instanceof AiWorkflowRun) {
                return $run;
            }
        }

        return null;
    }

    public function handleTranslationRequest(Model $source, string $contentText, array $locales = [], ?User $actor = null): ?AiWorkflowRun
    {
        $contentText = trim($contentText);

        if ($contentText === '') {
            return null;
        }

        $locales = $this->translationLocales($locales);

        if ($locales === []) {
            return null;
        }

        $workflow = $this->translationWorkflow($actor?->id);
        $idempotencyKey = $this->translationIdempotencyKey($workflow, $source, $contentText, $locales);

        $existing = AiWorkflowRun::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        $summary = $this->contentEmbeddingService->summarize($source);
        $run = $this->createWorkflowRun($workflow, $source, $actor, $summary, [
            'content_text' => $contentText,
            'locales' => $locales,
        ]);

        try {
            $result = $this->aiManager->generate(new AiRequestData(
                input: [
                    'operation' => 'translate',
                    'title' => (string) ($source->title ?? ''),
                    'content' => $contentText,
                    'locales' => $locales,
                    'prompt' => 'Translate the selected CMS content into the requested locales. Return localized drafts only.',
                ],
                options: [
                    'source_type' => $source::class,
                    'source_id' => $source->getKey(),
                    'locales' => $locales,
                ],
                provider: config('ai.default_provider', 'fake'),
                model: data_get(config('ai.models.writer'), 'model', 'fake-writer'),
                promptKey: 'workflow.request.translate',
                feature: 'writer',
            ));

            $translations = is_array($result->response['translations'] ?? null) ? $result->response['translations'] : [];
            $run->forceFill([
                'status' => 'succeeded',
                'result' => $result->response,
                'review_task' => $this->buildTranslationReviewTask($source, $translations, $locales),
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

    protected function publishSeoWorkflow(?int $ownerUserId = null): AiWorkflow
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

    protected function publishSocialWorkflow(?int $ownerUserId = null): AiWorkflow
    {
        return AiWorkflow::query()->firstOrCreate(
            ['key' => 'content.publish.social-drafts'],
            [
                'workflow_uuid' => (string) Str::uuid(),
                'name' => 'Publish social drafts',
                'trigger' => 'content.published',
                'version' => 1,
                'status' => 'enabled',
                'conditions' => [
                    'requires_published_status' => true,
                ],
                'steps' => [
                    ['key' => 'generate_social_variants', 'type' => 'ai.generate', 'status' => 'pending'],
                    ['key' => 'create_editor_review_task', 'type' => 'task.create', 'status' => 'pending'],
                ],
                'owner_user_id' => $ownerUserId,
            ]
        );
    }

    protected function translationWorkflow(?int $ownerUserId = null): AiWorkflow
    {
        return AiWorkflow::query()->firstOrCreate(
            ['key' => 'content.request.translation-drafts'],
            [
                'workflow_uuid' => (string) Str::uuid(),
                'name' => 'Request translation drafts',
                'trigger' => 'content.translation_requested',
                'version' => 1,
                'status' => 'enabled',
                'conditions' => [
                    'requires_localized_output' => true,
                ],
                'steps' => [
                    ['key' => 'generate_translation_draft', 'type' => 'ai.generate', 'status' => 'pending'],
                    ['key' => 'create_editor_review_task', 'type' => 'task.create', 'status' => 'pending'],
                ],
                'owner_user_id' => $ownerUserId,
            ]
        );
    }

    /**
     * @param array<string, mixed> $summary
     */
    protected function runSeoWorkflow(Model $source, ?User $actor, array $summary, string $contentText): AiWorkflowRun
    {
        $workflow = $this->publishSeoWorkflow($actor?->id);
        $idempotencyKey = $this->idempotencyKey($workflow, $source);

        $existing = AiWorkflowRun::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        $run = $this->createWorkflowRun($workflow, $source, $actor, $summary, [
            'missing_fields' => $this->missingSeoFields($source),
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
                'review_task' => $this->buildSeoReviewTask($source, $seo),
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

    /**
     * @param array<string, mixed> $summary
     */
    protected function runSocialWorkflow(Model $source, ?User $actor, array $summary, string $contentText): AiWorkflowRun
    {
        $workflow = $this->publishSocialWorkflow($actor?->id);
        $idempotencyKey = $this->idempotencyKey($workflow, $source);

        $existing = AiWorkflowRun::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        $run = $this->createWorkflowRun($workflow, $source, $actor, $summary, [
            'channels' => $this->socialChannels(),
        ]);

        try {
            $result = $this->aiManager->generate(new AiRequestData(
                input: [
                    'operation' => 'social',
                    'title' => (string) ($source->title ?? ''),
                    'content' => $contentText,
                    'prompt' => 'Draft short social post variants for a published CMS item. Return multiple platform-ready variants.',
                ],
                options: [
                    'source_type' => $source::class,
                    'source_id' => $source->getKey(),
                    'channels' => $run->payload['channels'] ?? [],
                ],
                provider: config('ai.default_provider', 'fake'),
                model: data_get(config('ai.models.writer'), 'model', 'fake-writer'),
                promptKey: 'workflow.publish.social',
                feature: 'writer',
            ));

            $social = is_array($result->response['social_variants'] ?? null) ? $result->response['social_variants'] : [];
            $run->forceFill([
                'status' => 'succeeded',
                'result' => $result->response,
                'review_task' => $this->buildSocialReviewTask($source, $social),
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

    /**
     * @param array<string, mixed> $payload
     */
    protected function createWorkflowRun(AiWorkflow $workflow, Model $source, ?User $actor, array $summary, array $payload = []): AiWorkflowRun
    {
        return AiWorkflowRun::query()->create([
            'workflow_id' => $workflow->id,
            'run_uuid' => (string) Str::uuid(),
            'idempotency_key' => $this->idempotencyKey($workflow, $source),
            'trigger' => $workflow->trigger,
            'source_type' => $source::class,
            'source_id' => $source->getKey(),
            'actor_user_id' => $actor?->id,
            'status' => 'running',
            'started_at' => now(),
            'payload' => array_merge([
                'source_snapshot' => $summary,
            ], $payload),
        ]);
    }

    protected function translationIdempotencyKey(AiWorkflow $workflow, Model $source, string $contentText, array $locales): string
    {
        return hash('sha256', implode('|', [
            $workflow->key,
            $workflow->version,
            $source::class,
            (string) $source->getKey(),
            hash('sha256', $contentText),
            implode(',', $locales),
        ]));
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
    protected function buildSeoReviewTask(Model $source, array $seo): array
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
     * @param array<int, array<string, mixed>> $socialVariants
     * @return array<string, mixed>
     */
    protected function buildSocialReviewTask(Model $source, array $socialVariants): array
    {
        return [
            'status' => 'open',
            'assignee_role' => 'Editor',
            'title' => 'Review social drafts for ' . trim((string) ($source->title ?? class_basename($source))),
            'details' => [
                'channels' => $this->socialChannels(),
                'variants' => $socialVariants,
            ],
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $translations
     * @param array<int, string> $locales
     * @return array<string, mixed>
     */
    protected function buildTranslationReviewTask(Model $source, array $translations, array $locales): array
    {
        return [
            'status' => 'open',
            'assignee_role' => 'Editor',
            'title' => 'Review translation drafts for ' . trim((string) ($source->title ?? class_basename($source))),
            'details' => [
                'locales' => $locales,
                'translations' => $translations,
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

    /**
     * @return array<int, string>
     */
    protected function socialChannels(): array
    {
        return ['x', 'linkedin', 'facebook'];
    }

    /**
     * @param array<int, string> $locales
     * @return array<int, string>
     */
    protected function translationLocales(array $locales = []): array
    {
        $supported = ['hi', 'gu'];

        $locales = array_values(array_filter(array_map(
            static fn ($locale): string => strtolower(trim((string) $locale)),
            $locales
        )));

        if ($locales === []) {
            $locales = $supported;
        }

        return array_values(array_unique(array_values(array_intersect($locales, $supported))));
    }
}
