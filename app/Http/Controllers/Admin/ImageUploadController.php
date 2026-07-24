<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['nullable', 'image', 'max:5120'],
            'url' => ['nullable', 'url'],
        ]);

        abort_unless($request->hasFile('file') || $request->filled('url'), 422, 'An image file or URL is required.');

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('uploads/' . date('Y/m'), 'public');
            $filename = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $size = $file->getSize();
            $altText = pathinfo($filename, PATHINFO_FILENAME);
        } else {
            $remoteUrl = $request->string('url')->trim()->toString();
            $response = Http::timeout(15)->get($remoteUrl);

            abort_unless($response->successful(), 422, 'Unable to download the remote image.');

            $mimeType = $response->header('Content-Type', '');
            abort_unless(str_starts_with($mimeType, 'image/'), 422, 'The remote URL must point to an image.');

            $extension = match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'image/svg+xml' => 'svg',
                default => Str::after($mimeType, '/'),
            };

            $filename = basename(parse_url($remoteUrl, PHP_URL_PATH) ?: 'image.' . $extension);
            if (! str_contains($filename, '.')) {
                $filename .= '.' . $extension;
            }

            $path = 'uploads/' . date('Y/m') . '/' . Str::uuid() . '-' . Str::slug(pathinfo($filename, PATHINFO_FILENAME) ?: 'image') . '.' . $extension;
            Storage::disk('public')->put($path, $response->body());
            $size = Storage::disk('public')->size($path);
            $altText = pathinfo($filename, PATHINFO_FILENAME);
        }

        $publicUrl = Storage::disk('public')->url($path);

        $media = Media::create([
            'disk' => 'public',
            'path' => $path,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'size' => $size,
            'alt_text' => $altText,
        ]);

        return response()->json([
            'success' => 1,
            'file' => [
                'url' => $publicUrl,
                'media_id' => $media->id,
            ],
            'location' => $publicUrl,
            'media_id' => $media->id,
        ]);
    }
}
