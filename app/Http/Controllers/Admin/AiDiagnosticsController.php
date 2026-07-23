<?php

namespace App\Http\Controllers\Admin;

use App\AI\Contracts\AiProvider as AiProviderContract;
use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Support\Arr;
use Throwable;

class AiDiagnosticsController extends Controller
{
    public function index(SettingService $settings)
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
            'settings' => [
                'enabled' => $settings->get('ai.enabled', $config['enabled'] ?? false),
                'default_provider' => $settings->get('ai.default_provider', $config['default_provider'] ?? 'fake'),
                'fallback_provider' => $settings->get('ai.fallback_provider', $config['fallback_provider'] ?? 'fake'),
                'writer_enabled' => $settings->get('ai.writer.enabled', true),
                'chat_enabled' => $settings->get('ai.chat.enabled', true),
                'image_enabled' => $settings->get('ai.image.enabled', true),
            ],
        ]);
    }

    protected function buildProviderStatus(string $name, array $providerConfig): array
    {
        $driver = $providerConfig['driver'] ?? null;
        $capabilities = array_values(array_map('strval', $providerConfig['capabilities'] ?? []));

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
            'resolved_capabilities' => $resolvedCapabilities,
            'status' => $status,
            'enabled' => (bool) ($providerConfig['is_enabled'] ?? true),
        ];
    }
}
