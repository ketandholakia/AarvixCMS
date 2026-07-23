<?php

namespace App\AI\Services;

use App\AI\DTOs\AiScope;
use App\Models\AiConversation;
use App\Models\AiMessage;
use Illuminate\Support\Str;

class ChatService
{
    public function __construct(
        protected RetrievalService $retrievalService,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function createConversation(AiScope $scope, array $attributes = []): AiConversation
    {
        return AiConversation::query()->create(array_merge([
            'conversation_uuid' => (string) Str::uuid(),
            'user_id' => $scope->userId,
            'scope' => $scope->toArray(),
            'status' => 'active',
            'model_settings' => [],
        ], $attributes));
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function appendMessage(AiConversation $conversation, string $role, string $content, array $payload = []): AiMessage
    {
        $messageOrder = ((int) $conversation->messages()->max('message_order')) + 1;

        $message = $conversation->messages()->create(array_merge([
            'role' => $role,
            'content' => $content,
            'message_order' => $messageOrder,
        ], $payload));

        $conversation->forceFill([
            'last_message_at' => now(),
        ])->save();

        return $message;
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function retrieveForConversation(AiConversation $conversation, string $question, int $limit = 5, array $options = []): array
    {
        $scope = new AiScope(
            userId: $conversation->user_id,
            site: data_get($conversation->scope, 'site'),
            feature: 'chat',
            metadata: is_array($conversation->scope['metadata'] ?? null) ? $conversation->scope['metadata'] : [],
        );

        return $this->retrievalService->retrieve($scope, $question, $limit, $options);
    }
}
