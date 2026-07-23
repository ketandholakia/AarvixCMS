<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAgentRun;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiAgentRunController extends Controller
{
    public function index(Request $request): View
    {
        $runs = AiAgentRun::query()
            ->with(['actor'])
            ->when($request->filled('agent_key'), fn ($query) => $query->where('agent_key', (string) $request->string('agent_key')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->string('status')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.ai-agent-runs.index', [
            'runs' => $runs,
            'filters' => [
                'agent_key' => $request->string('agent_key')->toString(),
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function show(AiAgentRun $ai_agent_run): View
    {
        $ai_agent_run->loadMissing(['actor', 'steps.toolCall.tool']);

        return view('admin.ai-agent-runs.show', [
            'run' => $ai_agent_run,
        ]);
    }
}
