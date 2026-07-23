<?php

namespace App\Console\Commands;

use App\AI\Services\RagEvaluationService;
use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;

class AiRagEvaluate extends Command
{
    protected $signature = 'ai:rag-evaluate {--fixture=tests/Fixtures/AI/rag-eval/v1.json : Path to a versioned RAG evaluation fixture}';

    protected $description = 'Evaluate retrieval recall, citation correctness, and injection safety against a versioned fixture set.';

    public function handle(RagEvaluationService $service): int
    {
        $fixturePath = base_path((string) $this->option('fixture'));

        if (! is_file($fixturePath)) {
            $this->error('Fixture not found: ' . $fixturePath);

            return self::FAILURE;
        }

        $fixture = json_decode((string) file_get_contents($fixturePath), true);

        if (! is_array($fixture)) {
            throw new RuntimeException('RAG evaluation fixture must be valid JSON.');
        }

        try {
            $report = $service->evaluate($fixture);
        } catch (InvalidArgumentException $e) {
            $this->error('RAG evaluation failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info('RAG evaluation: ' . $report['version']);
        $this->table(['Metric', 'Value'], [
            ['Cases', (string) $report['case_count']],
            ['Recall', number_format((float) $report['recall'] * 100, 2) . '%'],
            ['Citation correctness', number_format((float) $report['citation_correctness'] * 100, 2) . '%'],
            ['Injection safety', number_format((float) $report['injection_safety'] * 100, 2) . '%'],
        ]);

        foreach ($report['cases'] as $case) {
            $this->line(sprintf(
                '- %s: recall=%s, citations=%s',
                $case['name'],
                number_format((float) $case['recall'] * 100, 2) . '%',
                implode(', ', $case['retrieved_titles'])
            ));
        }

        return self::SUCCESS;
    }
}
