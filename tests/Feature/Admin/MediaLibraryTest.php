<?php

namespace Tests\Feature\Admin;

use App\Models\AiImageAsset;
use App\Models\AiMediaAnalysis;
use App\Models\Media;
use App\Models\Role;
use App\Models\User;
use App\Jobs\AnalyzeMediaVisionJob;
use App\AI\Providers\FakeAiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;
use App\Models\AiRequest;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach(Role::where('name', 'Admin')->first());

        return $user;
    }

    public function test_admin_can_see_ai_image_metadata_in_the_media_library(): void
    {
        $media = Media::create([
            'disk' => 'public',
            'path' => 'uploads/generated.webp',
            'filename' => 'generated.webp',
            'mime_type' => 'image/webp',
            'size' => 1024,
            'alt_text' => 'Generated image',
            'caption' => 'Generated image caption',
        ]);

        AiImageAsset::create([
            'media_id' => $media->id,
            'ai_request_id' => AiRequest::create([
                'request_uuid' => 'img-req-1',
                'feature' => 'image',
                'status' => 'succeeded',
                'provider' => 'fake',
                'model' => 'fake-image',
            ])->id,
            'provider' => 'fake',
            'model' => 'fake-image',
            'operation' => 'generate',
            'alt_text' => 'Generated image',
            'caption' => 'Generated image caption',
            'tags' => ['ai', 'generated'],
            'ocr_text' => 'Visible text from the generated image.',
            'prompt_hash' => hash('sha256', 'prompt body'),
            'moderation_status' => 'approved',
            'estimated_cost' => '0.00000000',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.media.index'));

        $response->assertStatus(200);
        $response->assertSee('AI image');
        $response->assertSee('generate');
        $response->assertSee('approved');
        $response->assertSee('ai, generated');
        $response->assertSee('Visible text from the generated image.');
        $response->assertSee('img-req-1');
    }

    public function test_media_library_returns_paginated_json_for_picker_requests(): void
    {
        Media::create([
            'disk' => 'public',
            'path' => 'uploads/picker-one.webp',
            'filename' => 'picker-one.webp',
            'mime_type' => 'image/webp',
            'size' => 1024,
            'alt_text' => 'Picker one',
            'caption' => 'Picker caption',
        ]);

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.media.index', ['search' => 'picker']));

        $response->assertOk()
            ->assertJsonPath('data.0.filename', 'picker-one.webp')
            ->assertJsonPath('data.0.alt_text', 'Picker one')
            ->assertJsonPath('data.0.caption', 'Picker caption');
    }

    public function test_admin_can_open_a_media_detail_page_with_ai_provenance(): void
    {
        $media = Media::create([
            'disk' => 'public',
            'path' => 'uploads/generated-detail.webp',
            'filename' => 'generated-detail.webp',
            'mime_type' => 'image/webp',
            'size' => 2048,
            'alt_text' => 'Detail image',
            'caption' => 'Detail caption',
        ]);

        AiImageAsset::create([
            'media_id' => $media->id,
            'ai_request_id' => AiRequest::create([
                'request_uuid' => 'img-req-2',
                'feature' => 'image',
                'status' => 'succeeded',
                'provider' => 'fake',
                'model' => 'fake-image',
            ])->id,
            'provider' => 'fake',
            'model' => 'fake-image',
            'operation' => 'edit',
            'alt_text' => 'Detail image',
            'caption' => 'Detail caption',
            'tags' => ['detail', 'ai'],
            'ocr_text' => 'Detail OCR text.',
            'prompt_hash' => hash('sha256', 'detail prompt'),
            'moderation_status' => 'pending',
            'estimated_cost' => '0.00000000',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.media.show', $media));

        $response->assertStatus(200);
        $response->assertSee('Media Details');
        $response->assertSee('AI Provenance');
        $response->assertSee('fake-image');
        $response->assertSee('edit');
        $response->assertSee('pending');
        $response->assertSee('detail, ai');
        $response->assertSee('Detail OCR text.');
        $response->assertSee(hash('sha256', 'detail prompt'));
        $response->assertSee('img-req-2');
    }

    public function test_admin_can_queue_vision_analysis_for_image_media(): void
    {
        Bus::fake();

        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);

        $media = Media::create([
            'disk' => 'public',
            'path' => 'uploads/analyze-me.webp',
            'filename' => 'analyze-me.webp',
            'mime_type' => 'image/webp',
            'size' => 2048,
            'alt_text' => 'Analyze me',
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.media.analyze', $media));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'AI vision analysis has been queued.');

        Bus::assertDispatched(AnalyzeMediaVisionJob::class, function (AnalyzeMediaVisionJob $job) use ($media) {
            return $job->mediaId === $media->id
                && $job->analysisType === 'vision'
                && $job->provider === 'fake'
                && $job->model === 'fake-vision';
        });
    }

    public function test_admin_can_queue_screenshot_analysis_for_image_media(): void
    {
        Bus::fake();

        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);

        $media = Media::create([
            'disk' => 'public',
            'path' => 'uploads/screenshot.webp',
            'filename' => 'screenshot.webp',
            'mime_type' => 'image/webp',
            'size' => 2048,
            'alt_text' => 'Screenshot',
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.media.analyze', $media), [
            'analysis_type' => 'screenshot',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'AI vision analysis has been queued.');

        Bus::assertDispatched(AnalyzeMediaVisionJob::class, function (AnalyzeMediaVisionJob $job) use ($media) {
            return $job->mediaId === $media->id
                && $job->analysisType === 'screenshot'
                && $job->provider === 'fake'
                && $job->model === 'fake-vision';
        });
    }

    public function test_vision_analysis_job_persists_a_structured_analysis_record(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);

        $media = Media::create([
            'disk' => 'public',
            'path' => 'uploads/product-target.webp',
            'filename' => 'product-target.webp',
            'mime_type' => 'image/webp',
            'size' => 4096,
            'width' => 1200,
            'height' => 800,
            'alt_text' => 'Product target',
            'caption' => 'Product target caption',
        ]);

        $job = new AnalyzeMediaVisionJob(
            mediaId: $media->id,
            userId: $this->admin()->id,
            provider: 'fake',
            model: 'fake-vision'
        );

        app()->call([$job, 'handle']);

        $analysis = AiMediaAnalysis::query()->firstOrFail();
        $aiRequest = $analysis->request()->first();

        $this->assertSame($media->id, $analysis->media_id);
        $this->assertSame('vision', $analysis->analysis_type);
        $this->assertSame('fake', $analysis->provider);
        $this->assertSame('fake-vision', $analysis->model);
        $this->assertSame('Vision analysis for product-target.webp.', $analysis->summary);
        $this->assertSame('Accessible description for product-target.webp', $analysis->alt_text);
        $this->assertSame('Generated vision caption for product-target.webp', $analysis->caption);
        $this->assertSame(['vision', 'analysis', 'image', 'webp'], $analysis->tags);
        $this->assertSame('Detected text from product-target.webp.', $analysis->ocr_text);
        $this->assertSame('product-target.webp', $analysis->structured_data['filename'] ?? null);
        $this->assertSame('image/webp', $analysis->structured_data['mime_type'] ?? null);
        $this->assertSame('product', $analysis->structured_data['classification']['label'] ?? null);
        $this->assertSame(0.94, $analysis->structured_data['classification']['confidence'] ?? null);
        $this->assertSame(hash('sha256', 'Analyze this media for accessibility, OCR, and structured extraction.'), $analysis->prompt_hash);
        $this->assertNotNull($analysis->analyzed_at);
        $this->assertNotNull($aiRequest);
        $this->assertNotEmpty($aiRequest->request_uuid);

        $response = $this->actingAs($this->admin())->get(route('admin.media.show', $media));
        $response->assertOk();
        $response->assertSee('Vision Analysis');
        $response->assertSee('Analyze with AI');
        $response->assertSee('Analyze screenshot');
        $response->assertSee('AI Request');
        $response->assertSee($aiRequest->request_uuid);
        $response->assertSee('Accessible description for product-target.webp');
        $response->assertSee('Detected text from product-target.webp.');
        $response->assertSee('Classification');
        $response->assertSee('product');
        $response->assertSee('94.0%');
    }

    public function test_screenshot_analysis_job_persists_a_screenshot_specific_record(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);

        $media = Media::create([
            'disk' => 'public',
            'path' => 'uploads/screenshot-target.webp',
            'filename' => 'screenshot-target.webp',
            'mime_type' => 'image/webp',
            'size' => 4096,
            'width' => 1440,
            'height' => 900,
            'alt_text' => 'Screenshot target',
            'caption' => 'Screenshot target caption',
        ]);

        $job = new AnalyzeMediaVisionJob(
            mediaId: $media->id,
            analysisType: 'screenshot',
            userId: $this->admin()->id,
            provider: 'fake',
            model: 'fake-vision'
        );

        app()->call([$job, 'handle']);

        $analysis = AiMediaAnalysis::query()->firstOrFail();
        $aiRequest = $analysis->request()->first();

        $this->assertSame('screenshot', $analysis->analysis_type);
        $this->assertSame('Screenshot analysis for screenshot-target.webp.', $analysis->summary);
        $this->assertSame('Accessible screenshot description for screenshot-target.webp', $analysis->alt_text);
        $this->assertSame('Generated screenshot caption for screenshot-target.webp', $analysis->caption);
        $this->assertSame(['vision', 'analysis', 'screenshot', 'image', 'webp'], $analysis->tags);
        $this->assertSame('Detected UI text from screenshot-target.webp.', $analysis->ocr_text);
        $this->assertSame('screenshot', $analysis->structured_data['analysis_type'] ?? null);
        $this->assertSame('screenshot', $analysis->structured_data['classification']['label'] ?? null);
        $this->assertSame(0.97, $analysis->structured_data['classification']['confidence'] ?? null);
        $this->assertNotNull($aiRequest);
        $this->assertNotEmpty($aiRequest->request_uuid);

        $response = $this->actingAs($this->admin())->get(route('admin.media.show', $media));
        $response->assertOk();
        $response->assertSee('AI Request');
        $response->assertSee($aiRequest->request_uuid);
    }
}
