<?php

namespace App\Console\Commands;

use App\AI\DTOs\AiRequestData;
use App\AI\Services\AiManager;
use Illuminate\Console\Command;
use Throwable;

class AiDiagnose extends Command
{
    protected $signature = 'ai:diagnose {--provider= : Override the configured AI provider} {--model= : Override the configured AI model}';

    protected $description = 'Run a minimal AI generation request for deployment diagnostics.';

    public function handle(AiManager $aiManager): int
    {
        $provider = $this->option('provider') ?: config('ai.default_provider', 'fake');
        $model = $this->option('model') ?: data_get(config('ai.models.writer'), 'model', 'fake-writer');

        $this->info('AI diagnostic request');
        $this->line("Provider: {$provider}");
        $this->line("Model: {$model}");

        try {
            $result = $aiManager->generate(new AiRequestData(
                input: [
                    'prompt' => 'Diagnostics ping.',
                ],
                provider: is_string($provider) && $provider !== '' ? $provider : null,
                model: is_string($model) && $model !== '' ? $model : null,
                feature: 'diagnostics',
            ));
        } catch (Throwable $e) {
            $this->error('AI diagnostics failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $summary = is_array($result->response)
            ? (string) ($result->response['plain_text'] ?? json_encode($result->response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
            : (string) $result->response;

        $this->table(['Field', 'Value'], [
            ['Status', $result->status->value],
            ['Request ID', (string) ($result->requestId ?? 'n/a')],
            ['Provider', $result->provider ?: $provider],
            ['Model', $result->model ?: $model],
            ['Tokens', (string) ($result->usage?->totalTokens ?? 0)],
            ['Cost', (string) ($result->usage?->estimatedCost ?? '0.000000')],
            ['Summary', $summary !== '' ? $summary : 'n/a'],
        ]);

        return self::SUCCESS;
    }
}
