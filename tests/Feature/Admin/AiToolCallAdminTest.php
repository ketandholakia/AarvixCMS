<?php

namespace Tests\Feature\Admin;

use App\AI\Services\AiToolRegistryService;
use App\Models\AiToolCall;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiToolCallAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(\Database\Seeders\AiToolSeeder::class);
    }

    public function test_admin_can_view_tool_calls_and_approve_a_pending_call(): void
    {
        $author = User::factory()->create(['is_active' => true]);
        $author->roles()->attach(Role::where('name', 'Author')->firstOrFail());

        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->firstOrFail());

        $draftResult = app(AiToolRegistryService::class)->execute(
            'content.draft',
            [
                'title' => 'Admin review article',
                'body' => 'Draft content for admin review.',
            ],
            $author,
            ['site' => 'main'],
        );

        $call = AiToolCall::query()->latest('id')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.ai-tool-calls.index'))
            ->assertOk()
            ->assertSee('content.draft')
            ->assertSee('Awaiting approval');

        $this->actingAs($admin)
            ->get(route('admin.ai-tool-calls.show', $call))
            ->assertOk()
            ->assertSee('Admin review article');

        $this->actingAs($admin)
            ->post(route('admin.ai-tool-calls.approve', $call))
            ->assertRedirect(route('admin.ai-tool-calls.show', $call));

        $call->refresh();
        $this->assertSame('approved', $call->approval_state);
        $this->assertSame('succeeded', $call->status);
        $this->assertSame($admin->id, $call->approved_by_user_id);

        $post = Post::query()->latest('id')->firstOrFail();
        $this->assertSame('Admin review article', $post->title);
        $this->assertSame($author->id, $post->author_id);
        $this->assertSame('draft', $post->status);
    }
}
