<?php

namespace Tests\Feature\AI;

use App\AI\DTOs\AiScope;
use App\AI\Services\ChatService;
use App\AI\Support\VectorStores\InMemoryVectorStore;
use App\Jobs\SyncContentEmbeddingsJob;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
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
}
