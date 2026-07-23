<?php

namespace App\AI\Contracts;

use App\AI\DTOs\AiRequestData;
use App\AI\DTOs\AiResult;

interface AiProvider
{
    public function name(): string;

    /**
     * @return array<int, string>
     */
    public function capabilities(): array;

    public function generate(AiRequestData $request): AiResult;

    public function stream(AiRequestData $request): iterable;

    public function chat(AiRequestData $request): AiResult;

    public function embedding(AiRequestData $request): AiResult;

    public function image(AiRequestData $request): AiResult;

    public function vision(AiRequestData $request): AiResult;

    public function json(AiRequestData $request): AiResult;
}
