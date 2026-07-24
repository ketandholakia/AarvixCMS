<?php

namespace App\Console\Commands;

use App\AI\Enums\AiStatus;
use App\Models\AiRequest;
use Illuminate\Console\Command;

class AiReconcileRequests extends Command
{
    protected $signature = 'ai:reconcile-requests {--minutes=60 : Mark requests older than this many minutes as timed out}';

    protected $description = 'Reconcile stale pending and running AI requests.';

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $cutoff = now()->subMinutes($minutes);

        $reconciled = 0;
        $requests = AiRequest::query()
            ->whereIn('status', ['pending', 'running'])
            ->orderBy('id')
            ->get();

        foreach ($requests->filter(function (AiRequest $request) use ($cutoff): bool {
            $startedAt = $request->started_at ?? $request->created_at;

            return $startedAt !== null && $startedAt->lt($cutoff);
        }) as $request) {
            $startedAt = $request->started_at ?? $request->created_at ?? now();

            $request->forceFill([
                'status' => AiStatus::TimedOut->value,
                'error_class' => 'TimeoutException',
                'error_message' => 'AI request exceeded the reconciliation window.',
                'latency_ms' => max((int) ($request->latency_ms ?? 0), (int) $startedAt->diffInMilliseconds(now())),
                'completed_at' => now(),
            ])->save();

            $reconciled++;
        }

        $this->info('Reconciled ' . $reconciled . ' stale AI request(s) older than ' . $minutes . ' minute(s).');

        return self::SUCCESS;
    }
}
