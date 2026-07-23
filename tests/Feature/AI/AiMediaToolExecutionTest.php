<?php

namespace Tests\Feature\AI;

use App\AI\Services\AiToolRegistryService;
use App\Models\AiToolCall;
use App\Models\Media;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiMediaToolExecutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(\Database\Seeders\AiToolSeeder::class);
    }

    public function test_media_search_tool_returns_matching_media_and_records_audit_data(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->firstOrFail());

        $media = Media::create([
            'disk' => 'public',
            'path' => 'uploads/launch-checklist.webp',
            'filename' => 'launch-checklist.webp',
            'original_filename' => 'Launch Checklist.webp',
            'mime_type' => 'image/webp',
            'size' => 4096,
            'width' => 1200,
            'height' => 800,
            'alt_text' => 'Launch checklist image',
            'caption' => 'Launch checklist for the operations team',
            'uploaded_by' => $admin->id,
        ]);

        $result = app(AiToolRegistryService::class)->execute(
            'media.search',
            [
                'query' => 'launch checklist',
                'limit' => 5,
                'images_only' => true,
            ],
            $admin,
            ['site' => 'main'],
        );

        $this->assertSame(1, $result['count']);
        $this->assertSame($media->id, $result['items'][0]['id']);
        $this->assertSame('launch-checklist.webp', $result['items'][0]['filename']);
        $this->assertStringContainsString('launch-checklist.webp', $result['items'][0]['url']);

        $call = AiToolCall::query()->latest('id')->firstOrFail();
        $this->assertSame('succeeded', $call->status);
        $this->assertSame('media.search', $call->tool->key);
        $this->assertSame(1, $call->result_summary['count']);
        $this->assertSame('launch-checklist.webp', $call->result_summary['items'][0]['filename']);
    }
}
