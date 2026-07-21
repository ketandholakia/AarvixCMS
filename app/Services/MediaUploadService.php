<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
}
