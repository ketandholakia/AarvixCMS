<?php

namespace App\Http\Controllers\Admin;

use App\AI\Services\AiPolicyService;
use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeMediaVisionJob;
use App\Models\Media;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $query = Media::with('aiImageAsset')->latest();

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

    public function show(string $id)
    {
        $media = Media::with(['aiImageAsset', 'aiVisionAnalysis'])->findOrFail($id);

        return view('admin.media.show', compact('media'));
    }

    public function analyze(Request $request, Media $media, AiPolicyService $policy, SettingService $settings): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if (! $media->isImage()) {
            abort(422, 'Only image media can be analyzed.');
        }

        $policy->assertEnabled('vision');
        $analysisType = $request->string('analysis_type')->toString() === 'screenshot' ? 'screenshot' : 'vision';

        $provider = $settings->get('ai.default_provider', config('ai.default_provider', 'fake'));
        $model = $settings->get('ai.models.vision.model', data_get(config('ai.models.vision'), 'model', 'fake-vision'));

        AnalyzeMediaVisionJob::dispatch(
            mediaId: $media->id,
            analysisType: $analysisType,
            userId: $request->user()?->id,
            provider: $provider,
            model: $model,
        )->onQueue(config('ai.queue.low', 'ai-low'));

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'queued',
                'queue' => config('ai.queue.low', 'ai-low'),
                'message' => 'AI vision analysis has been queued.',
                'analysis_type' => $analysisType,
            ], 202);
        }

        return back()->with('success', 'AI vision analysis has been queued.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file'     => ['required', 'image', 'max:10240'], // 10MB
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('file');
        $path = $file->store('uploads/' . date('Y/m'), 'public');

        $media = new Media([
            'disk'      => 'public',
            'path'      => $path,
            'original_filename' => $file->getClientOriginalName(),
            'filename'  => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size'      => $file->getSize(),
            'alt_text'  => $request->input('alt_text', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)),
            'caption'   => $request->input('caption'),
            'uploaded_by' => auth()->id(),
        ]);
        
        if (str_starts_with($media->mime_type, 'image/')) {
            // Optimize image using Intervention Image v3
            $manager = \Intervention\Image\ImageManager::gd();
            $image = $manager->read(Storage::disk('public')->path($path));
            
            // Constrain maximum width to 2048px while maintaining aspect ratio
            if ($image->width() > 2048) {
                $image->scale(width: 2048);
                $image->save();
                
                // Update size after resize
                $media->size = Storage::disk('public')->size($path);
            }
            
            $media->width = $image->width();
            $media->height = $image->height();
        }
        
        $media->save();

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
