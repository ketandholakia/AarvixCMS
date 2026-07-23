<?php

namespace App\Services;

use App\Models\AiImageAsset;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MediaUploadService
{
    /**
     * Upload an image, strip EXIF, convert to WebP, and create a thumbnail.
     */
    public function uploadImage(UploadedFile $file, string $disk = 'public', string $path = 'uploads'): Media
    {
        $manager = new ImageManager(new Driver());
        
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $filename = \Illuminate\Support\Str::slug($originalName) . '-' . uniqid() . '.webp';
        
        // Load image, which natively strips EXIF in Intervention v3 unless preserved
        $image = $manager->read($file->getRealPath());
        
        // Convert to WebP with 80% quality
        $encodedImage = $image->toWebp(80);
        
        // Save original
        $fullPath = $path . '/' . $filename;
        Storage::disk($disk)->put($fullPath, $encodedImage->toString());
        
        // Create Thumbnail
        $thumbName = 'thumb-' . $filename;
        $thumbPath = $path . '/thumbs/' . $thumbName;
        
        $thumb = $image->scaleDown(width: 300, height: 300);
        Storage::disk($disk)->put($thumbPath, $thumb->toWebp(80)->toString());
        
        return Media::create([
            'disk' => $disk,
            'path' => $path,
            'filename' => $filename,
            'mime_type' => 'image/webp',
            'size' => Storage::disk($disk)->size($fullPath),
            'alt_text' => $originalName,
        ]);
    }

    /**
     * Persist generated image bytes through the same sanitization/conversion path.
     */
    public function uploadGeneratedImage(string $contents, string $originalName, string $disk = 'public', string $path = 'uploads', ?string $altText = null, ?string $caption = null): Media
    {
        $tempDir = storage_path('app/ai-generated');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $tempPath = $tempDir . DIRECTORY_SEPARATOR . Str::uuid() . '-' . basename($originalName);
        file_put_contents($tempPath, $contents);

        try {
            $media = $this->uploadImage(
                new UploadedFile($tempPath, $originalName, null, null, true),
                $disk,
                $path
            );

            if ($altText !== null || $caption !== null) {
                $media->fill([
                    'alt_text' => $altText ?? $media->alt_text,
                    'caption' => $caption ?? $media->caption,
                ])->save();
            }

            return $media->fresh();
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    public function replaceMediaWithGeneratedImage(Media $media, string $contents, string $originalName, ?string $altText = null, ?string $caption = null): Media
    {
        $staged = $this->uploadGeneratedImage($contents, $originalName, $media->disk, 'uploads/generated', $altText, $caption);
        $oldDisk = $media->disk;
        $oldPath = $media->path;
        $oldFilename = $media->filename;

        $media->fill([
            'disk' => $staged->disk,
            'path' => $staged->path,
            'filename' => $staged->filename,
            'mime_type' => $staged->mime_type,
            'size' => $staged->size,
            'alt_text' => $staged->alt_text,
            'caption' => $staged->caption,
            'width' => $staged->width,
            'height' => $staged->height,
        ])->save();

        if ($oldPath !== $media->path) {
            Storage::disk($oldDisk)->delete([$oldPath, dirname($oldPath) . '/thumbs/thumb-' . $oldFilename]);
        }

        $staged->delete();

        return $media->fresh();
    }

    public function deleteMedia(Media $media): void
    {
        Storage::disk($media->disk)->delete([
            $media->path,
            dirname($media->path) . '/thumbs/thumb-' . $media->filename,
        ]);

        $media->delete();
    }

    public function createAiImageAsset(Media $media, array $attributes): AiImageAsset
    {
        return $media->aiImageAsset()->create([
            'source_media_id' => $attributes['source_media_id'] ?? null,
            'ai_request_id' => $attributes['ai_request_id'] ?? null,
            'provider' => $attributes['provider'],
            'model' => $attributes['model'],
            'operation' => $attributes['operation'],
            'alt_text' => $attributes['alt_text'] ?? null,
            'caption' => $attributes['caption'] ?? null,
            'tags' => $attributes['tags'] ?? null,
            'ocr_text' => $attributes['ocr_text'] ?? null,
            'prompt_hash' => $attributes['prompt_hash'],
            'resolution' => $attributes['resolution'] ?? null,
            'seed' => $attributes['seed'] ?? null,
            'moderation_status' => $attributes['moderation_status'] ?? 'approved',
            'moderation_reviewed_at' => $attributes['moderation_reviewed_at'] ?? now(),
            'retention_expires_at' => $attributes['retention_expires_at'] ?? null,
            'estimated_cost' => $attributes['estimated_cost'] ?? 0,
            'metadata' => $attributes['metadata'] ?? null,
        ]);
    }
}
