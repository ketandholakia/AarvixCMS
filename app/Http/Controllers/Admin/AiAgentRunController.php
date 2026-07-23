<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAgentRun;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiAgentRunController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $query = AiAgentRun::query()
            ->with(['actor'])
            ->when($request->filled('agent_key'), fn ($query) => $query->where('agent_key', (string) $request->string('agent_key')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->string('status')))
            ->latest('id');

        if ($request->string('format')->toString() === 'csv') {
            return $this->exportCsv($query->get());
        }

        $runs = $query->paginate(20)->withQueryString();

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

    protected function exportCsv(iterable $runs): StreamedResponse
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            abort(500, 'Unable to export AI agent runs.');
        }

        $headers = [
            'run_uuid',
            'agent_key',
            'agent_name',
            'status',
            'actor',
            'steps_planned',
            'steps_completed',
            'estimated_tokens',
            'estimated_cost',
            'created_at',
        ];

        fputcsv($handle, $headers);

        foreach ($runs as $run) {
            fputcsv($handle, [
                $run->run_uuid,
                $run->agent_key,
                $run->agent_name,
                $run->status,
                $run->actor?->name ?? $run->actor?->email ?? '',
                $run->steps_planned,
                $run->steps_completed,
                $run->estimated_tokens,
                (string) $run->estimated_cost,
                optional($run->created_at)->toISOString(),
            ]);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return response()->streamDownload(
            static function () use ($csv): void {
                echo $csv;
            },
            'ai-agent-runs-' . now()->format('Y-m-d-His') . '.csv',
            ['Content-Type' => 'text/csv']
        );
    }
}
