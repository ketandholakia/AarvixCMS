<?php

namespace App\AI\Providers;

class GeminiProvider extends OpenAiProvider
{
    protected function providerName(): string
    {
        return 'gemini';
    }

    protected function providerConfigKey(): string
    {
        return 'gemini';
    }

    protected function providerLabel(): string
    {
        return 'Gemini';
    }

    protected function defaultChatModel(): string
    {
        return 'gemini-2.5-flash';
    }

    protected function defaultEmbeddingModel(): string
    {
        return 'gemini-embedding-001';
    }
}
