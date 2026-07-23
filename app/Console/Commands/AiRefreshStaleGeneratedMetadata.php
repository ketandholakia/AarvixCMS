<?php

namespace App\Console\Commands;

use App\Models\Entry;
use App\Models\Page;
use App\Models\Post;
use App\Services\WorkflowService;
use Illuminate\Console\Command;

class AiRefreshStaleGeneratedMetadata extends Command
{
    protected $signature = 'ai:refresh-stale-generated-metadata {--limit=50 : Maximum content items to inspect}';

    protected $description = 'Refresh stale AI-generated metadata and review tasks for published content.';

    public function handle(WorkflowService $workflowService): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $inspected = 0;
        $refreshedSources = 0;
        $refreshedRuns = 0;

        foreach ([Post::class, Page::class, Entry::class] as $modelClass) {
            $sources = $modelClass::query()
                ->where('status', 'published')
                ->latest('updated_at')
                ->limit($limit)
                ->get();

            foreach ($sources as $source) {
                if ($inspected >= $limit) {
                    break 2;
                }

                $inspected++;

                if (! $workflowService->isGeneratedMetadataStale($source)) {
                    continue;
                }

                $runs = $workflowService->refreshGeneratedMetadata($source);
                $refreshedSources++;
                $refreshedRuns += count($runs);
            }
        }

        $this->info(sprintf(
            'Refreshed %d stale content item(s) across %d workflow run(s).',
            $refreshedSources,
            $refreshedRuns
        ));

        return self::SUCCESS;
    }
}
