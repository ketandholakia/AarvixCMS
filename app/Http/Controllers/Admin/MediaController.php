<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $query = Media::latest();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('filename', 'like', "%{$search}%")
                  ->orWhere('alt_text', 'like', "%{$search}%");
            });
        }

        $records = $query->paginate(24)->withQueryString();

        // If called from a modal picker (AJAX), return JSON
        if ($request->expectsJson()) {
            return response()->json($records);
        }

        return view('admin.media.index', compact('records'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file'     => ['required', 'image', 'max:10240'], // 10MB
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('file');
        $path = $file->store('uploads/' . date('Y/m'), 'public');

        $media = Media::create([
            'disk'      => 'public',
            'path'      => $path,
            'filename'  => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size'      => $file->getSize(),
            'alt_text'  => $request->input('alt_text', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['media' => $media, 'url' => $media->url]);
        }

        return back()->with('success', 'File uploaded successfully.');
    }

    public function destroy(string $id)
    {
        $media = Media::findOrFail($id);

        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        return back()->with('success', 'Media deleted successfully.');
    }
}
