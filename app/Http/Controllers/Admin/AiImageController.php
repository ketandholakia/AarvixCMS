<?php

namespace App\Http\Controllers\Admin;

use App\AI\Exceptions\AiImageCapabilityException;
use App\AI\Exceptions\AiImagePolicyException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AiImageRequest;
use App\Jobs\GenerateAiImageJob;
use App\Services\AiImagePolicyService;
use App\Services\AiImageCapabilityService;

class AiImageController extends Controller
{
    public function generate(AiImageRequest $request, AiImageCapabilityService $capabilities, AiImagePolicyService $policy)
    {
        $data = $request->validated();

        if (! empty($data['replace_media_id']) && ! $request->boolean('confirm_replace')) {
            return response()->json([
                'message' => 'Confirm replacement before overwriting an existing media asset.',
                'errors' => [
                    'confirm_replace' => ['Confirm replacement before overwriting an existing media asset.'],
                ],
            ], 422);
        }

        try {
            $policy->assertPublicGenerationAllowed((bool) ($data['public_generation'] ?? false));
            $capabilities->assertSupported($data, config('ai.default_provider', 'fake'));
        } catch (AiImagePolicyException|AiImageCapabilityException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => method_exists($e, 'errors') ? $e->errors() : [],
            ], 422);
        }

        GenerateAiImageJob::dispatch(
            prompt: $data['prompt'],
            operation: $data['operation'],
            sourceMediaId: $data['source_media_id'] ?? null,
            replaceMediaId: $data['replace_media_id'] ?? null,
            resolution: $data['resolution'] ?? null,
            seed: $data['seed'] ?? null,
            publicGeneration: (bool) ($data['public_generation'] ?? false),
            userId: $request->user()?->id,
            provider: config('ai.default_provider', 'fake'),
            model: data_get(config('ai.models.image'), 'model', 'fake-image'),
        )->onQueue(config('ai.queue.low', 'ai-low'));

        return response()->json([
            'status' => 'queued',
            'queue' => config('ai.queue.low', 'ai-low'),
            'message' => 'AI image generation has been queued.',
        ], 202);
    }
}
