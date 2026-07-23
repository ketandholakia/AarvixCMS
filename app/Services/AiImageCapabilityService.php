<?php

namespace App\Services;

use App\AI\Exceptions\AiImageCapabilityException;

class AiImageCapabilityService
{
    /**
     * Validate image operations and option support for the active provider.
     *
     * @throws AiImageCapabilityException
     */
    public function assertSupported(array $data, ?string $provider = null): void
    {
        $providerName = $provider ?: config('ai.default_provider', 'fake');
        $providerConfig = config('ai.providers.' . $providerName, []);
        $imageConfig = is_array($providerConfig['image'] ?? null) ? $providerConfig['image'] : [];
        $errors = [];

        $operation = (string) ($data['operation'] ?? 'generate');
        $sourceMediaId = $data['source_media_id'] ?? null;
        $resolution = $data['resolution'] ?? null;
        $seed = $data['seed'] ?? null;

        $supportsEdit = $this->supports($imageConfig, 'supports_edit');
        $supportsMask = $this->supports($imageConfig, 'supports_mask', $supportsEdit);
        $supportsSeed = $this->supports($imageConfig, 'supports_seed');
        $supportsResolution = $this->supports($imageConfig, 'supports_resolution');

        if (in_array($operation, ['edit', 'upscale', 'resize'], true) && ! $supportsEdit) {
            $errors['operation'][] = sprintf(
                'AI provider [%s] does not support image operation [%s].',
                $providerName,
                $operation,
            );
        }

        if ($operation === 'remove_background' && ! $supportsMask) {
            $errors['operation'][] = sprintf(
                'AI provider [%s] does not support background removal.',
                $providerName,
            );
        }

        if (in_array($operation, ['edit', 'remove_background', 'upscale', 'resize'], true) && ! filled($sourceMediaId)) {
            $errors['source_media_id'][] = 'A source media asset is required for this image operation.';
        }

        if ($seed !== null && ! $supportsSeed) {
            $errors['seed'][] = sprintf(
                'AI provider [%s] does not support seed control.',
                $providerName,
            );
        }

        if ($resolution !== null && ! $supportsResolution) {
            $errors['resolution'][] = sprintf(
                'AI provider [%s] does not support custom image resolutions.',
                $providerName,
            );
        }

        if ($errors !== []) {
            throw new AiImageCapabilityException(
                'The selected AI provider does not support one or more requested image options.',
                $errors,
            );
        }
    }

    protected function supports(array $config, string $key, bool $default = true): bool
    {
        return filter_var($config[$key] ?? $default, FILTER_VALIDATE_BOOLEAN);
    }
}
