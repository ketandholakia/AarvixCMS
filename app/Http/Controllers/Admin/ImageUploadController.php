<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageUploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'image', 'max:5120'], // max 5MB
        ]);

        $file = $request->file('file');
        $path = $file->store('uploads/' . date('Y/m'), 'public');

        // Persist a Media record so the library can track all uploads
        $media = Media::create([
            'disk'      => 'public',
            'path'      => $path,
            'filename'  => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size'      => $file->getSize(),
            'alt_text'  => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
        ]);

        return response()->json([
            'location'  => Storage::disk('public')->url($path),
            'media_id'  => $media->id,
        ]);
    }
}
