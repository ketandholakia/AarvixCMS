<?php

namespace App\Services;

use App\AI\Exceptions\AiImagePolicyException;
use Carbon\CarbonInterface;

class AiImagePolicyService
{
    /**
     * @throws AiImagePolicyException
     */
    public function assertPublicGenerationAllowed(bool $publicGeneration): void
    {
        if (! $publicGeneration) {
            return;
        }

        if (! filter_var(config('ai.image.public_generation_enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            throw new AiImagePolicyException('Public AI image generation is not enabled.');
        }
    }

    public function moderationStatus(bool $publicGeneration): string
    {
        return $publicGeneration ? 'pending' : 'approved';
    }

    public function moderationReviewedAt(bool $publicGeneration): ?CarbonInterface
    {
        return $publicGeneration ? null : now();
    }

    public function retentionExpiresAt(?CarbonInterface $createdAt = null): ?CarbonInterface
    {
        $days = (int) config('ai.image.retention_days', 30);

        if ($days <= 0) {
            return null;
        }

        return ($createdAt ?? now())->copy()->addDays($days);
    }
}
