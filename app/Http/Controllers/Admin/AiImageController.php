<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AiImageRequest;
use App\Jobs\GenerateAiImageJob;

class AiImageController extends Controller
{
    public function generate(AiImageRequest $request)
    {
        $data = $request->validated();

        GenerateAiImageJob::dispatch(
            prompt: $data['prompt'],
            operation: $data['operation'],
            sourceMediaId: $data['source_media_id'] ?? null,
            resolution: $data['resolution'] ?? null,
            seed: $data['seed'] ?? null,
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
