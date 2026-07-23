<?php

namespace Tests\Feature\AI;

use App\AI\DTOs\AiScope;
use App\AI\Services\ChatService;
use App\AI\Providers\FakeAiProvider;
use App\AI\Support\VectorStores\InMemoryVectorStore;
use App\Jobs\SyncContentEmbeddingsJob;
use App\Models\AiChatRun;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ChatServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);
        config()->set('ai.vector_store.driver', InMemoryVectorStore::class);
        config()->set('ai.vector_store.collection', 'content_embeddings');
        config()->set('ai.embeddings.chunker_version', '1');
        config()->set('ai.embeddings.model', 'text-embedding-3-small');
    }

    public function test_chat_service_persists_conversations_and_messages(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach(Role::where('name', 'Admin')->firstOrFail());

        $conversation = $this->app->make(ChatService::class)->createConversation(
            new AiScope(userId: $user->id, site: 'default', feature: 'chat'),
            ['title' => 'Launch chat']
        );

        $this->assertInstanceOf(AiConversation::class, $conversation);
        $this->assertSame($user->id, $conversation->user_id);
        $this->assertSame('Launch chat', $conversation->title);

        $message = $this->app->make(ChatService::class)->appendMessage(
            $conversation,
            'user',
            'Where is the launch checklist?',
            ['request_uuid' => 'request-123']
        );

        $this->assertInstanceOf(AiMessage::class, $message);
        $this->assertSame('user', $message->role);
        $this->assertSame('Where is the launch checklist?', $message->content);
        $this->assertSame('request-123', $message->request_uuid);
        $this->assertNotNull($conversation->fresh()->last_message_at);
    }

    public function test_chat_service_can_retrieve_authorized_context_for_a_conversation(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->firstOrFail());

        $post = Post::withoutEvents(function () {
            return Post::factory()->create([
                'title' => 'Conversation source',
                'excerpt' => 'Conversation summary',
                'body' => json_encode([
                    'blocks' => [
                        ['type' => 'paragraph', 'data' => ['text' => 'Conversation body text.']],
                    ],
                ]),
                'status' => 'published',
            ]);
        });

        app()->call([new SyncContentEmbeddingsJob(Post::class, $post->id, 'chat-source-1'), 'handle']);

        $conversation = $this->app->make(ChatService::class)->createConversation(
            new AiScope(userId: $admin->id, site: 'default', feature: 'chat')
        );

        $result = $this->app->make(ChatService::class)->retrieveForConversation($conversation, 'conversation source');

        $this->assertNotEmpty($result['citations']);
        $this->assertSame('Conversation source', $result['citations'][0]['title']);
    }

    public function test_chat_service_supports_search_summary_and_policy_explanations(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->firstOrFail());

        $conversation = $this->app->make(ChatService::class)->createConversation(
            new AiScope(userId: $admin->id, site: 'default', feature: 'chat')
        );

        $service = $this->app->make(ChatService::class);

        $service->appendMessage($conversation, 'user', 'First question', ['request_uuid' => 'msg-1']);
        $service->appendMessage($conversation, 'assistant', 'First answer', ['request_uuid' => 'msg-2']);
        $service->appendMessage($conversation, 'user', 'Second question', ['request_uuid' => 'msg-3']);

        $summary = $service->summarizeConversation($conversation);
        $this->assertStringContainsString('Conversation summary:', $summary);
        $this->assertStringContainsString('User: First question', $summary);
        $this->assertStringContainsString('Assistant: First answer', $summary);

        $policy = $service->explainPolicy($conversation);
        $this->assertTrue($policy['is_admin']);
        $this->assertSame(['public', 'restricted', 'private'], $policy['allowed_visibilities']);
        $this->assertStringContainsString('Admin conversations', $policy['explanation']);

        $search = $service->searchContent($conversation, 'second question');
        $this->assertIsArray($search['citations']);
    }

    public function test_chat_service_allows_owner_and_admin_to_manage_conversations(): void
    {
        $owner = User::factory()->create(['is_active' => true]);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->firstOrFail());
        $other = User::factory()->create(['is_active' => true]);

        $service = $this->app->make(ChatService::class);

        $renamedConversation = $service->createConversation(
            new AiScope(userId: $owner->id, site: 'default', feature: 'chat'),
            ['title' => 'Original title']
        );
        $service->renameConversation($renamedConversation, 'Renamed conversation', $owner);
        $this->assertSame('Renamed conversation', $renamedConversation->fresh()->title);

        $archivedConversation = $service->createConversation(
            new AiScope(userId: $owner->id, site: 'default', feature: 'chat'),
            ['title' => 'Archive me']
        );
        $service->archiveConversation($archivedConversation, $admin);
        $this->assertSame('archived', $archivedConversation->fresh()->status);

        $deletedConversation = $service->createConversation(
            new AiScope(userId: $owner->id, site: 'default', feature: 'chat'),
            ['title' => 'Delete me']
        );
        $service->deleteConversation($deletedConversation, $owner);
        $this->assertSoftDeleted($deletedConversation);

        $unauthorizedConversation = $service->createConversation(
            new AiScope(userId: $owner->id, site: 'default', feature: 'chat'),
            ['title' => 'Private']
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You are not allowed to manage this conversation.');
        $service->renameConversation($unauthorizedConversation, 'Nope', $other);
    }

    public function test_chat_service_normalizes_unknown_modes_to_knowledge(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->firstOrFail());

        $conversation = $this->app->make(ChatService::class)->createConversation(
            new AiScope(userId: $admin->id, site: 'default', feature: 'chat')
        );

        $run = $this->app->make(ChatService::class)->createRun($conversation, 'What mode is this?', [
            'mode' => 'unsupported-mode',
        ]);

        $this->assertSame('knowledge', $run->mode);
    }

    public function test_chat_service_streams_answers_and_supports_cancel_and_retry(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->firstOrFail());

        $post = Post::withoutEvents(function () {
            return Post::factory()->create([
                'title' => 'Streaming source',
                'excerpt' => 'Streaming summary',
                'body' => json_encode([
                    'blocks' => [
                        ['type' => 'paragraph', 'data' => ['text' => 'Streaming body text.']],
                    ],
                ]),
                'status' => 'published',
            ]);
        });

        app()->call([new SyncContentEmbeddingsJob(Post::class, $post->id, 'chat-stream-source'), 'handle']);

        $conversation = $this->app->make(ChatService::class)->createConversation(
            new AiScope(userId: $admin->id, site: 'default', feature: 'chat')
        );

        $service = $this->app->make(ChatService::class);
        $stream = $service->streamConversation($conversation, 'What is the streaming source?');

        $stream->rewind();
        $firstChunk = $stream->current();
        $this->assertIsString($firstChunk);
        $this->assertNotSame('', $firstChunk);

        $run = AiChatRun::query()->where('conversation_id', $conversation->id)->firstOrFail();
        $service->cancelRun($run, $admin->id);

        $stream->next();
        $stream->next();

        $run = $run->fresh();
        $this->assertSame('cancelled', $run->status);
        $this->assertNotNull($run->cancelled_at);
        $this->assertStringContainsString($firstChunk, (string) $run->response_text);

        $retryRun = $service->retryRun($run);
        $this->assertSame($run->id, $retryRun->retry_of_id);
        $this->assertSame('pending', $retryRun->status);

        $retryStream = $service->streamRun($retryRun);
        $retriedText = '';
        foreach ($retryStream as $chunk) {
            $retriedText .= $chunk;
        }

        $retryRun = $retryRun->fresh();
        $this->assertSame('succeeded', $retryRun->status);
        $this->assertNotSame('', $retriedText);
        $this->assertStringContainsString('What is the streaming source?', $retriedText);
        $this->assertNotEmpty($conversation->fresh()->messages()->where('role', 'assistant')->get());
    }
}
