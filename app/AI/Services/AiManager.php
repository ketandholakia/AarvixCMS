<?php

namespace App\AI\Services;

use App\AI\Contracts\AiProvider;
use App\AI\DTOs\AiRequestData;
use App\AI\DTOs\AiResult;
use App\AI\Enums\AiCapability;
use App\AI\Enums\AiStatus;
use App\AI\Exceptions\AiCapabilityException;
use App\AI\Exceptions\AiRateLimitException;
use App\AI\Exceptions\AiProviderException;
use App\AI\Services\UsageService;
use Illuminate\Contracts\Container\Container;
use Throwable;

class AiManager
{
    /**
     * @var array<string, AiProvider>
     */
    protected array $resolvedProviders = [];

    public function __construct(
        protected Container $container,
        protected array $config = [],
        protected ?UsageService $usageService = null,
    ) {
    }

    public function provider(?string $name = null): AiProvider
    {
        $name ??= $this->config['default_provider'] ?? 'fake';

        if (isset($this->resolvedProviders[$name])) {
            return $this->resolvedProviders[$name];
        }

        $providerConfig = $this->config['providers'][$name] ?? null;

        if (! is_array($providerConfig) || empty($providerConfig['driver'])) {
            $fallback = $this->config['fallback_provider'] ?? null;

            if (is_string($fallback) && isset($this->config['providers'][$fallback]['driver'])) {
                $name = $fallback;
                $providerConfig = $this->config['providers'][$name];
            } else {
                throw new AiProviderException("AI provider [{$name}] is not configured.");
            }
        }

        $driver = $providerConfig['driver'];
        $provider = $this->container->make($driver);

        if (! $provider instanceof AiProvider) {
            throw new AiProviderException("AI provider driver [{$driver}] must implement " . AiProvider::class . '.');
        }

        return $this->resolvedProviders[$name] = $provider;
    }

    public function generate(AiRequestData $request): AiResult
    {
        return $this->callResult(AiCapability::Generate, 'generate', $request);
    }

    public function stream(AiRequestData $request): iterable
    {
        return $this->callStream(AiCapability::Stream, 'stream', $request);
    }

    public function chat(AiRequestData $request): AiResult
    {
        return $this->callResult(AiCapability::Chat, 'chat', $request);
    }

    public function embedding(AiRequestData $request): AiResult
    {
        return $this->callResult(AiCapability::Embedding, 'embedding', $request);
    }

    public function image(AiRequestData $request): AiResult
    {
        return $this->callResult(AiCapability::Image, 'image', $request);
    }

    public function vision(AiRequestData $request): AiResult
    {
        return $this->callResult(AiCapability::Vision, 'vision', $request);
    }

    public function json(AiRequestData $request): AiResult
    {
        return $this->callResult(AiCapability::Json, 'json', $request);
    }

    protected function callResult(AiCapability $capability, string $method, AiRequestData $request): AiResult
    {
        $request = $request->feature === null ? $request->withFeature($method) : $request;
        $provider = $this->provider($request->provider);
        $this->assertCapability($provider, $capability);

        $requestRecord = $this->usageService?->logStart($request, $provider->name(), $request->model ?? $this->defaultModelFor($method, $provider->name()));

        $startedAt = microtime(true);

        try {
            $result = $provider->{$method}($request);

            if (! $result instanceof AiResult) {
                throw new AiProviderException(sprintf(
                    'AI provider [%s] returned an invalid result for [%s].',
                    $provider->name(),
                    $method,
                ));
            }

            $result = $result->withContext(
                provider: $provider->name(),
                model: $request->model ?? $result->model,
                latencyMs: (int) round((microtime(true) - $startedAt) * 1000),
            );

            if ($this->usageService && $requestRecord) {
                $this->usageService->logSuccess($requestRecord, $result);
            }

            return $result;
        } catch (AiRateLimitException $e) {
            if ($this->usageService && $requestRecord) {
                $this->usageService->logFailure($requestRecord, $e, AiStatus::RateLimited);
            }

            throw $e;
        } catch (Throwable $e) {
            if ($this->usageService && $requestRecord) {
                $this->usageService->logFailure($requestRecord, $e);
            }

            throw $e;
        }
    }

    protected function callStream(AiCapability $capability, string $method, AiRequestData $request): iterable
    {
        $request = $request->feature === null ? $request->withFeature($method) : $request;
        $provider = $this->provider($request->provider);
        $this->assertCapability($provider, $capability);

        return $provider->{$method}($request);
    }

    protected function defaultModelFor(string $feature, string $providerName): string
    {
        $featureConfig = $this->config['models'][$feature] ?? [];

        if (is_array($featureConfig) && isset($featureConfig['model']) && is_string($featureConfig['model'])) {
            return $featureConfig['model'];
        }

        return $providerName . '-model';
    }

    protected function assertCapability(AiProvider $provider, AiCapability $capability): void
    {
        if (! in_array($capability->value, $provider->capabilities(), true)) {
            throw new AiCapabilityException(sprintf(
                'AI provider [%s] does not support capability [%s].',
                $provider->name(),
                $capability->value,
            ));
        }
    }
}
