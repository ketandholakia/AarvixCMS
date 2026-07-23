<?php

namespace Tests\Feature\Console;

use App\AI\Support\VectorStores\InMemoryVectorStore;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiRagEvaluateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_rag_evaluate_command_reports_metrics_for_versioned_fixture(): void
    {
        config()->set('ai.vector_store.driver', InMemoryVectorStore::class);
        config()->set('ai.vector_store.collection', 'rag_eval');
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $admin = User::factory()->create(['id' => 1, 'is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->firstOrFail());

        $this->artisan('ai:rag-evaluate', [
            '--fixture' => 'tests/Fixtures/AI/rag-eval/v1.json',
        ])
            ->expectsOutputToContain('RAG evaluation: v1')
            ->expectsOutputToContain('Recall')
            ->expectsOutputToContain('Citation correctness')
            ->expectsOutputToContain('Injection safety')
            ->assertExitCode(0);
    }
}
