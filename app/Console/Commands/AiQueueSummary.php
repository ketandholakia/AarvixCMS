<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AiQueueSummary extends Command
{
    protected $signature = 'ai:queues';

    protected $description = 'Show the configured AI queue names and the suggested worker command.';

    public function handle(): int
    {
        $high = (string) config('ai.queue.high', 'ai-high');
        $medium = (string) config('ai.queue.medium', 'ai-medium');
        $low = (string) config('ai.queue.low', 'ai-low');

        $this->table(['Priority', 'Queue'], [
            ['High', $high],
            ['Medium', $medium],
            ['Low', $low],
        ]);

        $this->line('');
        $this->line('Suggested worker command:');
        $this->line('php artisan queue:work --queue=' . implode(',', [$high, $medium, $low]) . ' --tries=1 --timeout=0');

        return self::SUCCESS;
    }
}
