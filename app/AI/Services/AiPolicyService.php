<?php

namespace App\AI\Services;

use App\AI\Exceptions\AiRateLimitException;
use App\AI\Exceptions\AiTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use App\Services\SettingService;
use Throwable;

class AiPolicyService
{
    public function __construct(
        protected SettingService $settings,
    ) {
    }

    public function assertEnabled(?string $feature = null): void
    {
        if (! $this->isEnabled($feature)) {
            if ($feature !== null && $feature !== '') {
                throw new AiTimeoutException("AI feature [{$feature}] is disabled.");
            }

            throw new AiTimeoutException('AI is disabled.');
        }
    }

    public function isEnabled(?string $feature = null): bool
    {
        $enabled = $this->settings->get('ai.enabled', config('ai.enabled', false));

        if (! filter_var($enabled, FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        if ($feature === null || $feature === '') {
            return true;
        }

        $featureEnabled = $this->settings->get("ai.{$feature}.enabled", config("ai.{$feature}.enabled", true));

        return filter_var($featureEnabled, FILTER_VALIDATE_BOOLEAN);
    }

    public function retryAttempts(?string $provider = null): int
    {
        $providerAttempts = $provider ? data_get(config("ai.providers.{$provider}", []), 'retries') : null;

        if (is_int($providerAttempts) || ctype_digit((string) $providerAttempts)) {
            return max(0, (int) $providerAttempts);
        }

        return max(0, (int) data_get(config('ai.retry', []), 'attempts', 2));
    }

    public function retryDelayMs(): int
    {
        return max(0, (int) data_get(config('ai.retry', []), 'delay_ms', 250));
    }

    public function retryableStatusCodes(): array
    {
        $codes = data_get(config('ai.retry', []), 'retryable_status_codes', [408, 425, 429, 500, 502, 503, 504]);

        return array_values(array_unique(array_map('intval', is_array($codes) ? $codes : [])));
    }

    public function isRetryable(Throwable $throwable): bool
    {
        if ($throwable instanceof AiTimeoutException || $throwable instanceof AiRateLimitException) {
            return true;
        }

        if ($throwable instanceof ConnectionException) {
            return true;
        }

        if ($throwable instanceof RequestException) {
            $response = $throwable->response ?? null;
            $status = method_exists($response, 'status') ? (int) $response->status() : 0;

            return in_array($status, $this->retryableStatusCodes(), true);
        }

        return in_array((int) $throwable->getCode(), $this->retryableStatusCodes(), true);
    }
}
