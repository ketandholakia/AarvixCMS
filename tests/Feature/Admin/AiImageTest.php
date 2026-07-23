<?php

namespace Tests\Feature\Admin;

use App\AI\Providers\FakeAiProvider;
use App\Jobs\GenerateAiImageJob;
use App\Models\AiImageAsset;
use App\Models\Media;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiImageTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach(Role::where('name', 'Admin')->first());

        return $user;
    }

    public function test_admin_can_queue_ai_image_generation_on_the_low_queue(): void
    {
        Bus::fake();

        config()->set('ai.enabled', true);
        config()->set('ai.image.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);

        $response = $this->actingAs($this->admin())->postJson(route('admin.ai.images.generate'), [
            'prompt' => 'Sunrise over a futuristic city',
            'operation' => 'generate',
            'resolution' => '1024x1024',
        ]);

        $response->assertAccepted();
        $response->assertJsonPath('status', 'queued');
        $response->assertJsonPath('queue', 'ai-low');

        Bus::assertDispatched(GenerateAiImageJob::class, function (GenerateAiImageJob $job) {
            return $job->queue === 'ai-low'
                && $job->prompt === 'Sunrise over a futuristic city'
                && $job->operation === 'generate'
                && $job->resolution === '1024x1024';
        });
    }

    public function test_ai_image_job_persists_media_and_provenance(): void
    {
        Storage::fake('public');

        config()->set('ai.enabled', true);
        config()->set('ai.image.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);

        $job = new GenerateAiImageJob(
            prompt: 'Sunrise over a futuristic city',
            operation: 'generate',
            sourceMediaId: null,
            resolution: '1024x1024',
            seed: 12345,
            userId: $this->admin()->id,
            provider: 'fake',
            model: 'fake-image'
        );

        app()->call([$job, 'handle']);

        $media = Media::query()->firstOrFail();
        $asset = AiImageAsset::query()->firstOrFail();

        $this->assertSame('Generated AI image', $media->alt_text);
        $this->assertSame('Generated AI image preview', $media->caption);
        $this->assertSame($media->id, $asset->media_id);
        $this->assertSame('fake', $asset->provider);
        $this->assertSame('fake-image', $asset->model);
        $this->assertSame('generate', $asset->operation);
        $this->assertSame(hash('sha256', 'Sunrise over a futuristic city'), $asset->prompt_hash);
        $this->assertSame('1024x1024', $asset->resolution);
        $this->assertSame(12345, $asset->seed);
        $this->assertNotEmpty($asset->metadata['provider_request_id'] ?? null);
        $this->assertNotEmpty($asset->metadata['request_id'] ?? null);

        Storage::disk('public')->assertExists($media->path . '/' . $media->filename);
        Storage::disk('public')->assertExists($media->path . '/thumbs/thumb-' . $media->filename);
    }
}
