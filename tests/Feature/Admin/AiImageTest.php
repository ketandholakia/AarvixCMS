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

    public function test_admin_must_confirm_before_replacing_an_existing_media_asset(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.image.enabled', true);

        $media = Media::create([
            'disk' => 'public',
            'path' => 'uploads/existing.webp',
            'filename' => 'existing.webp',
            'mime_type' => 'image/webp',
            'size' => 1024,
            'alt_text' => 'Existing image',
        ]);

        $response = $this->actingAs($this->admin())->postJson(route('admin.ai.images.generate'), [
            'prompt' => 'Replace the existing image',
            'operation' => 'generate',
            'replace_media_id' => $media->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.confirm_replace.0', 'Confirm replacement before overwriting an existing media asset.');
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

    public function test_ai_image_job_can_replace_an_existing_media_asset_when_confirmed(): void
    {
        Storage::fake('public');

        config()->set('ai.enabled', true);
        config()->set('ai.image.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);

        Storage::disk('public')->put('uploads/existing.webp', 'old-image');
        Storage::disk('public')->put('uploads/thumbs/thumb-existing.webp', 'old-thumb');

        $media = Media::create([
            'disk' => 'public',
            'path' => 'uploads/existing.webp',
            'filename' => 'existing.webp',
            'mime_type' => 'image/webp',
            'size' => 1024,
            'alt_text' => 'Existing image',
            'caption' => 'Existing caption',
        ]);

        $job = new GenerateAiImageJob(
            prompt: 'Replace the existing image',
            operation: 'edit',
            sourceMediaId: $media->id,
            replaceMediaId: $media->id,
            resolution: '1024x1024',
            seed: 7,
            userId: $this->admin()->id,
            provider: 'fake',
            model: 'fake-image'
        );

        app()->call([$job, 'handle']);

        $media->refresh();
        $asset = AiImageAsset::query()->where('media_id', $media->id)->firstOrFail();

        $this->assertSame('Generated AI image', $media->alt_text);
        $this->assertSame('Generated AI image preview', $media->caption);
        $this->assertNotSame('uploads/existing.webp', $media->path);
        $this->assertSame($media->id, $asset->media_id);
        $this->assertSame($media->id, $asset->source_media_id);
        $this->assertSame('edit', $asset->operation);
        $this->assertSame('fake', $asset->provider);
        $this->assertSame('fake-image', $asset->model);

        Storage::disk('public')->assertMissing('uploads/existing.webp');
        Storage::disk('public')->assertMissing('uploads/thumbs/thumb-existing.webp');
        Storage::disk('public')->assertExists($media->path . '/' . $media->filename);
        Storage::disk('public')->assertExists($media->path . '/thumbs/thumb-' . $media->filename);
    }
}
