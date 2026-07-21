<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageUploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'image', 'max:5120'], // max 5MB
        ]);

        $path = $request->file('file')->store('uploads/' . date('Y/m'), 'public');
        
        return response()->json([
            'location' => Storage::url($path),
        ]);
    }
}
