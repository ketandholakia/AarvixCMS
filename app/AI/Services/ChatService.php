<?php

namespace App\AI\Services;

use App\AI\DTOs\AiRequestData;
use App\AI\DTOs\AiScope;
use App\Models\AiChatRun;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

class ChatService
{
    protected const SUPPORTED_MODES = ['knowledge', 'summary', 'policy', 'writing'];

    public function __construct(
        protected AiManager $aiManager,
        protected RetrievalService $retrievalService,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function createConversation(AiScope $scope, array $attributes = []): AiConversation
    {
        $modelSettings = is_array($attributes['model_settings'] ?? null) ? $attributes['model_settings'] : [];
        $defaultMode = $this->normalizeMode($attributes['mode'] ?? data_get($modelSettings, 'mode', 'knowledge'));

        unset($attributes['mode']);
        unset($attributes['model_settings']);

        return AiConversation::query()->create(array_merge([
            'conversation_uuid' => (string) Str::uuid(),
            'user_id' => $scope->userId,
            'scope' => $scope->toArray(),
            'status' => 'active',
            'model_settings' => array_merge($modelSettings, [
                'mode' => $defaultMode,
            ]),
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
     */
    public function createRun(AiConversation $conversation, string $question, array $options = [], ?AiChatRun $retryOf = null): AiChatRun
    {
        $conversationMode = $this->conversationMode($conversation);

        return AiChatRun::query()->create([
            'conversation_id' => $conversation->id,
            'retry_of_id' => $retryOf?->id,
            'request_uuid' => (string) Str::uuid(),
            'mode' => $this->normalizeMode($options['mode'] ?? $conversationMode),
            'status' => 'pending',
            'question' => $question,
            'options' => $options,
            'context' => [],
        ]);
    }

    /**
     * @param array<string, mixed> $options
     * @return iterable<string>
     */
    public function streamConversation(AiConversation $conversation, string $question, array $options = []): iterable
    {
        $run = $this->createRun($conversation, $question, $options);

        return $this->streamRun($run);
    }

    /**
     * @return iterable<string>
     */
    public function streamRun(AiChatRun $run): iterable
    {
        $run = $run->fresh() ?? $run;

        if ($run->status === 'cancelled') {
            return;
        }

        $conversation = $run->conversation()->firstOrFail();
        $scope = new AiScope(
            userId: $conversation->user_id,
            site: data_get($conversation->scope, 'site'),
            feature: 'chat',
            metadata: is_array($conversation->scope['metadata'] ?? null) ? $conversation->scope['metadata'] : [],
        );
        $mode = $this->normalizeMode($run->mode);
        $usesRetrieval = $this->usesRetrieval($mode);

        $run->forceFill([
            'status' => 'streaming',
            'started_at' => $run->started_at ?? now(),
        ])->save();

        $conversation->messages()->create([
            'role' => 'user',
            'content' => $run->question,
            'request_uuid' => $run->request_uuid,
            'message_order' => ((int) $conversation->messages()->max('message_order')) + 1,
        ]);

        $retrieval = $usesRetrieval
            ? $this->retrievalService->retrieve(
                $scope,
                $run->question,
                (int) ($run->options['limit'] ?? 5),
                is_array($run->options['retrieval'] ?? null) ? $run->options['retrieval'] : []
            )
            : [
                'context' => '',
                'citations' => [],
            ];

        $run->forceFill([
            'context' => $retrieval,
        ])->save();

        $prompt = $this->buildPrompt($run->question, $retrieval, $mode);
        $request = new AiRequestData(
            input: [
                'prompt' => $prompt,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->buildSystemPrompt($mode),
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'context' => $retrieval['context'] ?? '',
            ],
            scope: $scope,
            feature: 'chat',
            provider: is_string($run->options['provider'] ?? null) ? $run->options['provider'] : null,
            model: is_string($run->options['model'] ?? null) ? $run->options['model'] : null,
        );

        $buffer = '';
        $chunks = $this->aiManager->stream($request);

        foreach ($chunks as $chunk) {
            $run->refresh();

            if ($run->status === 'cancelled') {
                $run->forceFill([
                    'response_text' => $buffer,
                    'completed_at' => now(),
                ])->save();

                return;
            }

            $chunk = (string) $chunk;
            $buffer .= $chunk;
            $run->forceFill([
                'response_text' => $buffer,
            ])->save();

            yield $chunk;
        }

        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $buffer,
            'citations' => $retrieval['citations'] ?? [],
            'request_uuid' => $run->request_uuid,
            'message_order' => ((int) $conversation->messages()->max('message_order')) + 1,
        ]);

        $run->forceFill([
            'status' => 'succeeded',
            'response_text' => $buffer,
            'response_metadata' => [
                'citations' => $retrieval['citations'] ?? [],
            ],
            'completed_at' => now(),
        ])->save();
    }

    public function cancelRun(AiChatRun $run, ?int $userId = null): AiChatRun
    {
        if (in_array($run->status, ['succeeded', 'cancelled', 'failed'], true)) {
            return $run;
        }

        $run->forceFill([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by_user_id' => $userId,
        ])->save();

        return $run;
    }

    public function retryRun(AiChatRun $run): AiChatRun
    {
        $conversation = $run->conversation()->firstOrFail();

        return $this->createRun($conversation, $run->question, $run->options ?? [], $run);
    }

    public function setConversationMode(AiConversation $conversation, string $mode, ?User $actor = null): AiConversation
    {
        $this->authorizeConversationManagement($conversation, $actor);

        $mode = $this->normalizeMode($mode);
        $settings = is_array($conversation->model_settings ?? null) ? $conversation->model_settings : [];
        $settings['mode'] = $mode;

        $conversation->forceFill([
            'model_settings' => $settings,
        ])->save();

        return $conversation;
    }

    public function renameConversation(AiConversation $conversation, string $title, ?User $actor = null): AiConversation
    {
        $this->authorizeConversationManagement($conversation, $actor);

        $title = trim($title);

        if ($title === '') {
            throw new RuntimeException('Conversation title cannot be empty.');
        }

        $conversation->forceFill([
            'title' => $title,
        ])->save();

        return $conversation;
    }

    public function archiveConversation(AiConversation $conversation, ?User $actor = null): AiConversation
    {
        $this->authorizeConversationManagement($conversation, $actor);

        $conversation->forceFill([
            'status' => 'archived',
        ])->save();

        return $conversation;
    }

    public function deleteConversation(AiConversation $conversation, ?User $actor = null): void
    {
        $this->authorizeConversationManagement($conversation, $actor);

        $conversation->delete();
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function searchContent(AiConversation $conversation, string $question, int $limit = 5, array $options = []): array
    {
        $scope = $this->scopeForConversation($conversation);

        return $this->retrievalService->retrieve($scope, $question, $limit, $options);
    }

    public function summarizeConversation(AiConversation $conversation, int $limit = 10): string
    {
        $messages = $conversation->messages()
            ->orderBy('message_order')
            ->limit(max(1, $limit))
            ->get(['role', 'content'])
            ->map(static function (AiMessage $message): string {
                return ucfirst($message->role) . ': ' . trim((string) $message->content);
            })
            ->filter()
            ->values()
            ->all();

        if ($messages === []) {
            return 'No conversation messages yet.';
        }

        return 'Conversation summary: ' . implode(' | ', $messages);
    }

    /**
     * @return array<string, mixed>
     */
    public function explainPolicy(AiConversation $conversation): array
    {
        $scope = $this->scopeForConversation($conversation);
        $user = $conversation->user;
        $isAdmin = $user?->hasRole('Admin') ?? false;

        $allowedVisibilities = $isAdmin ? ['public', 'restricted', 'private'] : ['public'];

        return [
            'conversation_id' => $conversation->id,
            'owner_user_id' => $conversation->user_id,
            'is_admin' => $isAdmin,
            'allowed_visibilities' => $allowedVisibilities,
            'explanation' => $isAdmin
                ? 'Admin conversations can cite public, restricted, and private CMS content.'
                : 'Non-admin conversations can only cite public CMS content.',
            'scope' => $scope->toArray(),
        ];
    }

    /**
     * @param array<string, mixed> $retrieval
     */
    protected function buildPrompt(string $question, array $retrieval, string $mode = 'knowledge'): string
    {
        if ($mode === 'writing') {
            return trim(implode("\n\n", array_values(array_filter([
                'Writing help request: ' . $question,
                'Help the user draft, revise, or refine copy directly.',
                'Do not invent citations or claim access to CMS sources unless the user provides them.',
            ]))));
        }

        $context = (string) ($retrieval['context'] ?? '');
        $citations = array_map(
            static fn (array $citation): string => sprintf(
                '- %s (%s)',
                (string) ($citation['title'] ?? 'Untitled'),
                (string) ($citation['public_url'] ?? $citation['admin_url'] ?? 'no-url')
            ),
            is_array($retrieval['citations'] ?? null) ? $retrieval['citations'] : []
        );

        return trim(implode("\n\n", array_values(array_filter([
            'Question: ' . $question,
            $context !== '' ? 'Context: ' . $context : null,
            $citations !== [] ? 'Citations: ' . implode("\n", $citations) : null,
        ]))));
    }

    protected function buildSystemPrompt(string $mode): string
    {
        return $mode === 'writing'
            ? 'Help the user draft or revise writing. Do not cite CMS sources unless they are explicitly provided.'
            : 'Answer only using the provided context. Cite source titles when helpful.';
    }

    protected function normalizeMode(mixed $mode): string
    {
        $mode = is_string($mode) ? strtolower(trim($mode)) : 'knowledge';

        return in_array($mode, self::SUPPORTED_MODES, true) ? $mode : 'knowledge';
    }

    protected function conversationMode(AiConversation $conversation): string
    {
        $settings = is_array($conversation->model_settings ?? null) ? $conversation->model_settings : [];

        return $this->normalizeMode(data_get($settings, 'mode', 'knowledge'));
    }

    protected function usesRetrieval(string $mode): bool
    {
        return $mode !== 'writing';
    }

    protected function scopeForConversation(AiConversation $conversation): AiScope
    {
        return new AiScope(
            userId: $conversation->user_id,
            site: data_get($conversation->scope, 'site'),
            feature: 'chat',
            metadata: is_array($conversation->scope['metadata'] ?? null) ? $conversation->scope['metadata'] : [],
        );
    }

    protected function authorizeConversationManagement(AiConversation $conversation, ?User $actor = null): void
    {
        if ($actor === null) {
            throw new RuntimeException('Conversation management requires an actor.');
        }

        if ((int) $conversation->user_id !== (int) $actor->id && ! $actor->hasRole('Admin')) {
            throw new RuntimeException('You are not allowed to manage this conversation.');
        }
    }
}
