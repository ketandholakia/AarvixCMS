<?php

namespace App\Console\Commands;

use App\Models\AiRequest;
use App\Models\AiUsageDaily;
use Illuminate\Console\Command;

class AiPruneUsage extends Command
{
    protected $signature = 'ai:prune-usage {--days= : Retain request logs for this many days}';

    protected $description = 'Prune old AI request logs and aggregate records.';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('ai.logging.retention_days', 30));
        $cutoff = now()->subDays($days);

        $deletedRequests = AiRequest::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $deletedUsage = AiUsageDaily::query()
            ->where('usage_date', '<', now()->subDays(max($days, 90)))
            ->delete();

        $this->info("Deleted {$deletedRequests} AI request records older than {$days} days.");
        $this->info("Deleted {$deletedUsage} AI usage records older than the retention window.");

        return self::SUCCESS;
    }
}
