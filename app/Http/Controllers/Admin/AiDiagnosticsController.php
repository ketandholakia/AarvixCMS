<?php

namespace App\Http\Controllers\Admin;

use App\AI\Contracts\AiProvider as AiProviderContract;
use App\AI\Services\AiAgentRegistryService;
use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Support\Arr;
use Throwable;

class AiDiagnosticsController extends Controller
{
    public function index(SettingService $settings, AiAgentRegistryService $agents)
    {
        $config = config('ai', []);
        $providers = [];

        foreach (Arr::wrap($config['providers'] ?? []) as $name => $providerConfig) {
            $providerConfig = is_array($providerConfig) ? $providerConfig : [];

            $providers[] = $this->buildProviderStatus($name, $providerConfig);
        }

        return view('admin.ai.diagnostics', [
            'config' => $config,
            'providers' => $providers,
            'agents' => $agents->all()->map(static fn ($agent) => $agent->toArray())->all(),
            'settings' => [
                'enabled' => $settings->get('ai.enabled', $config['enabled'] ?? false),
                'default_provider' => $settings->get('ai.default_provider', $config['default_provider'] ?? 'fake'),
                'fallback_provider' => $settings->get('ai.fallback_provider', $config['fallback_provider'] ?? 'fake'),
                'writer_enabled' => $settings->get('ai.writer.enabled', true),
                'chat_enabled' => $settings->get('ai.chat.enabled', true),
                'image_enabled' => $settings->get('ai.image.enabled', true),
                'seo_agent_enabled' => $settings->get('ai.agents.seo.enabled', data_get($config, 'agents.seo.is_enabled', true)),
                'marketing_agent_enabled' => $settings->get('ai.agents.marketing.enabled', data_get($config, 'agents.marketing.is_enabled', true)),
                'translation_agent_enabled' => $settings->get('ai.agents.translation.enabled', data_get($config, 'agents.translation.is_enabled', true)),
                'documentation_agent_enabled' => $settings->get('ai.agents.documentation.enabled', data_get($config, 'agents.documentation.is_enabled', true)),
                'support_agent_enabled' => $settings->get('ai.agents.support.enabled', data_get($config, 'agents.support.is_enabled', true)),
                'image_public_generation_enabled' => $settings->get('ai.image.public_generation_enabled', data_get($config, 'providers.fake.image.public_generation_enabled', false)),
                'image_retention_days' => $settings->get('ai.image.retention_days', data_get($config, 'providers.fake.image.retention_days', 30)),
            ],
        ]);
    }

    protected function buildProviderStatus(string $name, array $providerConfig): array
    {
        $driver = $providerConfig['driver'] ?? null;
        $capabilities = array_values(array_map('strval', $providerConfig['capabilities'] ?? []));
        $imageCapabilities = is_array($providerConfig['image'] ?? null) ? $providerConfig['image'] : [];

        try {
            if (! is_string($driver) || $driver === '') {
                throw new \RuntimeException('Missing driver.');
            }

            $provider = app()->make($driver);

            if (! $provider instanceof AiProviderContract) {
                throw new \RuntimeException("Driver [{$driver}] does not implement the AI provider contract.");
            }

            $status = 'ready';
            $resolvedCapabilities = $provider->capabilities();
        } catch (Throwable $e) {
            $status = 'error';
            $resolvedCapabilities = [];
        }

        return [
            'name' => $name,
            'driver' => is_string($driver) ? $driver : 'n/a',
            'configured_capabilities' => $capabilities,
            'image_capabilities' => $imageCapabilities,
            'resolved_capabilities' => $resolvedCapabilities,
            'status' => $status,
            'enabled' => (bool) ($providerConfig['is_enabled'] ?? true),
        ];
    }
}
