<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = AiRequest::query()
            ->with(['user'])
            ->when($request->filled('feature'), fn ($query) => $query->where('feature', (string) $request->string('feature')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->string('status')))
            ->when($request->filled('provider'), fn ($query) => $query->where('provider', (string) $request->string('provider')))
            ->latest('id');

        return view('admin.ai-requests.index', [
            'requests' => $query->paginate(20)->withQueryString(),
            'filters' => [
                'feature' => $request->string('feature')->toString(),
                'status' => $request->string('status')->toString(),
                'provider' => $request->string('provider')->toString(),
            ],
        ]);
    }

    public function show(AiRequest $ai_request): View
    {
        $ai_request->loadMissing(['user']);

        return view('admin.ai-requests.show', [
            'request' => $ai_request,
        ]);
    }
}
