<?php

namespace App\Http\Controllers\Admin;

use App\AI\Services\AiToolRegistryService;
use App\Http\Controllers\Controller;
use App\Models\AiToolCall;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiToolCallController extends Controller
{
    public function index(Request $request): View
    {
        $calls = AiToolCall::query()
            ->with(['tool', 'actor', 'approvedBy'])
            ->when($request->filled('tool_key'), function ($query) use ($request): void {
                $toolKey = (string) $request->string('tool_key');

                $query->whereHas('tool', static fn ($toolQuery) => $toolQuery->where('key', $toolKey));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->string('status')))
            ->when($request->filled('approval_state'), fn ($query) => $query->where('approval_state', (string) $request->string('approval_state')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.ai-tool-calls.index', [
            'calls' => $calls,
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

    protected function validateApprovalRequest(Request $request): array
    {
        return $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
