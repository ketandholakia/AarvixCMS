<?php

namespace App\Console\Commands;

use App\AI\Services\AiManager;
use Illuminate\Console\Command;
use App\Services\SettingService;
use Throwable;

class AiHealthCheck extends Command
{
    protected $signature = 'ai:health';

    protected $description = 'Check AI configuration and provider connectivity.';

    public function handle(AiManager $aiManager, SettingService $settings): int
    {
        $this->info('AI health check');

        $enabled = filter_var($settings->get('ai.enabled', config('ai.enabled', false)), FILTER_VALIDATE_BOOLEAN);

        $this->table(['Setting', 'Value'], [
            ['Enabled', $enabled ? 'yes' : 'no'],
            ['Timeout', (string) config('ai.timeout', 60) . 's'],
            ['Retry attempts', (string) config('ai.retry.attempts', 2)],
            ['Retry delay', (string) config('ai.retry.delay_ms', 250) . 'ms'],
            ['Default provider', (string) config('ai.default_provider', 'n/a')],
            ['Fallback provider', (string) config('ai.fallback_provider', 'n/a')],
            ['High queue', (string) config('ai.queue.high', 'ai-high')],
            ['Medium queue', (string) config('ai.queue.medium', 'ai-medium')],
            ['Low queue', (string) config('ai.queue.low', 'ai-low')],
        ]);

        $rows = [];

        foreach (array_keys((array) config('ai.providers', [])) as $name) {
            try {
                $provider = $aiManager->provider((string) $name);
                $rows[] = [$name, 'ready', implode(', ', $provider->capabilities()) ?: 'n/a'];
            } catch (Throwable $e) {
                $rows[] = [$name, 'error', $e->getMessage()];
            }
        }

        if ($rows !== []) {
            $this->table(['Provider', 'Status', 'Details'], $rows);
        } else {
            $this->warn('No AI providers are configured.');
        }

        return self::SUCCESS;
    }
}
