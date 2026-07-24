<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AiRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->buildQuery($request);

        return view('admin.ai-requests.index', [
            'requests' => $query->paginate(20)->withQueryString(),
            'filters' => [
                'feature' => $request->string('feature')->toString(),
                'status' => $request->string('status')->toString(),
                'provider' => $request->string('provider')->toString(),
            ],
        ]);
    }

    public function export(Request $request): Response
    {
        $requests = $this->buildQuery($request)->get();

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            abort(500, 'Unable to export AI requests.');
        }

        fputcsv($handle, [
            'request_uuid',
            'feature',
            'status',
            'provider',
            'model',
            'prompt_key',
            'actor',
            'prompt_tokens',
            'completion_tokens',
            'total_tokens',
            'estimated_cost',
            'latency_ms',
            'created_at',
            'completed_at',
        ]);

        foreach ($requests as $requestModel) {
            fputcsv($handle, [
                $requestModel->request_uuid,
                $requestModel->feature,
                $requestModel->status,
                $requestModel->provider,
                $requestModel->model,
                $requestModel->prompt_key,
                $requestModel->user?->name ?? $requestModel->user?->email ?? '',
                $requestModel->prompt_tokens,
                $requestModel->completion_tokens,
                $requestModel->total_tokens,
                (string) $requestModel->estimated_cost,
                $requestModel->latency_ms,
                optional($requestModel->created_at)->toISOString(),
                optional($requestModel->completed_at)->toISOString(),
            ]);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="ai-requests-' . now()->format('Y-m-d-His') . '.csv"',
        ]);
    }

    public function show(AiRequest $ai_request): View
    {
        $ai_request->loadMissing(['user']);

        return view('admin.ai-requests.show', [
            'request' => $ai_request,
        ]);
    }

    protected function buildQuery(Request $request)
    {
        return AiRequest::query()
            ->with(['user'])
            ->when($request->filled('feature'), fn ($query) => $query->where('feature', (string) $request->string('feature')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->string('status')))
            ->when($request->filled('provider'), fn ($query) => $query->where('provider', (string) $request->string('provider')))
            ->latest('id');
    }
}
