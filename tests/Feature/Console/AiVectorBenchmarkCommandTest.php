<?php

namespace Tests\Feature\Console;

use App\AI\Support\VectorStores\InMemoryVectorStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiVectorBenchmarkCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_vector_benchmark_command_reports_store_metrics(): void
    {
        config()->set('ai.vector_store.driver', InMemoryVectorStore::class);

        $this->artisan('ai:vector-benchmark', [
            '--records' => 12,
            '--queries' => 6,
            '--collection' => 'test_embeddings',
        ])
            ->expectsOutputToContain('AI vector-store benchmark')
            ->expectsOutputToContain('Store: in-memory')
            ->expectsOutputToContain('Indexed records')
            ->expectsOutputToContain('Query throughput')
            ->assertExitCode(0);
    }
}
