<?php

namespace App\Http\Controllers\Admin;

use App\AI\Services\AiToolRegistryService;
use App\Http\Controllers\Controller;
use App\Models\AiToolCall;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AiToolCallController extends Controller
{
    public function index(Request $request): View|Response
    {
        $query = $this->buildQuery($request);

        if ($request->string('format')->toString() === 'csv') {
            return $this->exportCsv($query->get());
        }

        $calls = $query->paginate(20)->withQueryString();
        $summary = $this->buildSummary(clone $query);

        return view('admin.ai-tool-calls.index', [
            'calls' => $calls,
            'summary' => $summary,
            'filters' => [
                'tool_key' => $request->string('tool_key')->toString(),
                'status' => $request->string('status')->toString(),
                'approval_state' => $request->string('approval_state')->toString(),
            ],
        ]);
    }

    public function show(AiToolCall $ai_tool_call): View
    {
        $ai_tool_call->loadMissing(['tool', 'actor', 'approvedBy']);

        return view('admin.ai-tool-calls.show', [
            'call' => $ai_tool_call,
        ]);
    }

    public function approve(Request $request, AiToolCall $ai_tool_call, AiToolRegistryService $registry): RedirectResponse
    {
        $this->validateApprovalRequest($request);
        $actor = $request->user();

        $registry->approveCall($ai_tool_call, $actor);

        return redirect()
            ->route('admin.ai-tool-calls.show', $ai_tool_call)
            ->with('success', 'AI tool call approved and executed.');
    }

    public function reject(Request $request, AiToolCall $ai_tool_call, AiToolRegistryService $registry): RedirectResponse
    {
        $this->validateApprovalRequest($request);
        $actor = $request->user();
        $reason = trim((string) $request->input('reason', ''));

        $registry->rejectCall($ai_tool_call, $actor, $reason !== '' ? $reason : null);

        return redirect()
            ->route('admin.ai-tool-calls.show', $ai_tool_call)
            ->with('success', 'AI tool call rejected.');
    }

    protected function buildQuery(Request $request)
    {
        return AiToolCall::query()
            ->with(['tool', 'actor', 'approvedBy'])
            ->when($request->filled('tool_key'), function ($query) use ($request): void {
                $toolKey = (string) $request->string('tool_key');

                $query->whereHas('tool', static fn ($toolQuery) => $toolQuery->where('key', $toolKey));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->string('status')))
            ->when($request->filled('approval_state'), fn ($query) => $query->where('approval_state', (string) $request->string('approval_state')))
            ->latest('id');
    }

    protected function buildSummary($query): array
    {
        $calls = $query->get();

        return [
            'total_calls' => $calls->count(),
            'awaiting_approval_count' => $calls->where('status', 'awaiting_approval')->count(),
            'approved_count' => $calls->where('approval_state', 'approved')->count(),
            'failed_count' => $calls->where('status', 'failed')->count(),
        ];
    }

    protected function exportCsv(iterable $calls): Response
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            abort(500, 'Unable to export AI tool calls.');
        }

        fputcsv($handle, [
            'call_uuid',
            'tool_key',
            'tool_name',
            'status',
            'approval_state',
            'actor',
            'request_uuid',
            'source_type',
            'source_id',
            'approved_by',
            'started_at',
            'completed_at',
            'created_at',
        ]);

        foreach ($calls as $call) {
            fputcsv($handle, [
                $call->call_uuid,
                $call->tool?->key ?? '',
                $call->tool?->name ?? '',
                $call->status,
                $call->approval_state,
                $call->actor?->name ?? $call->actor?->email ?? '',
                $call->request_uuid,
                $call->source_type,
                $call->source_id,
                $call->approvedBy?->name ?? $call->approvedBy?->email ?? '',
                optional($call->started_at)->toISOString(),
                optional($call->completed_at)->toISOString(),
                optional($call->created_at)->toISOString(),
            ]);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="ai-tool-calls-' . now()->format('Y-m-d-His') . '.csv"',
        ]);
    }

    protected function validateApprovalRequest(Request $request): array
    {
        return $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
