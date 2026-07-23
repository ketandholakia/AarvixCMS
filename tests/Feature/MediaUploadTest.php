<?php

namespace Tests\Feature;

use App\Models\AiImageAsset;
use App\Models\Media;
use App\Services\MediaUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_image_converts_to_webp_and_creates_thumbnail(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for image upload processing.');
        }

        Storage::fake('public');

        $gif = base64_decode('R0lGODlhAQABAPAAAP///wAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==');
        $tempDir = storage_path('app/testing');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $tempPath = $tempDir . DIRECTORY_SEPARATOR . 'photo.gif';
        file_put_contents($tempPath, $gif);
        $file = new UploadedFile($tempPath, 'photo.gif', 'image/gif', null, true);

        $service = new MediaUploadService();
        $media = $service->uploadImage($file, 'public', 'uploads');

        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals('image/webp', $media->mime_type);
        $this->assertStringEndsWith('.webp', $media->filename);

        // Assert files exist on fake disk
        Storage::disk('public')->assertExists('uploads/' . $media->filename);
        Storage::disk('public')->assertExists('uploads/thumbs/thumb-' . $media->filename);
    }

    public function test_create_ai_image_asset_links_metadata_to_media(): void
    {
        Storage::fake('public');

        $media = Media::create([
            'disk' => 'public',
            'path' => 'uploads/generated.webp',
            'filename' => 'generated.webp',
            'mime_type' => 'image/webp',
            'size' => 1024,
            'alt_text' => 'Generated image',
        ]);

        $service = new MediaUploadService();
        $asset = $service->createAiImageAsset($media, [
            'provider' => 'fake-image',
            'model' => 'vision-image-1',
            'operation' => 'generate',
            'prompt_hash' => hash('sha256', 'prompt body'),
            'resolution' => '1024x1024',
            'seed' => 12345,
            'estimated_cost' => '0.12500000',
            'metadata' => [
                'style' => 'editorial',
            ],
        ]);

        $this->assertInstanceOf(AiImageAsset::class, $asset);
        $this->assertSame($media->id, $asset->media_id);
        $this->assertSame('fake-image', $asset->provider);
        $this->assertSame('vision-image-1', $asset->model);
        $this->assertSame('generate', $asset->operation);
        $this->assertSame('1024x1024', $asset->resolution);
        $this->assertSame(12345, $asset->seed);
        $this->assertSame('editorial', $asset->metadata['style']);
        $this->assertTrue($media->fresh()->aiImageAsset()->exists());
    }
}
