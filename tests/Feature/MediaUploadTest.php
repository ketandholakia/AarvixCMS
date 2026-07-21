<?php

namespace Tests\Feature;

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
        Storage::fake('public');

        // Create a fake image using Laravel's UploadedFile helper
        $file = UploadedFile::fake()->image('photo.jpg', 600, 600);

        $service = new MediaUploadService();
        $media = $service->uploadImage($file, 'public', 'uploads');

        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals('image/webp', $media->mime_type);
        $this->assertStringEndsWith('.webp', $media->filename);

        // Assert files exist on fake disk
        Storage::disk('public')->assertExists('uploads/' . $media->filename);
        Storage::disk('public')->assertExists('uploads/thumbs/thumb-' . $media->filename);
    }
}
