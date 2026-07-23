<?php

namespace Tests\Feature\AI;

use App\AI\Services\RagEvaluationService;
use App\AI\Support\VectorStores\InMemoryVectorStore;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RagEvaluationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_rag_evaluation_service_calculates_recall_and_injection_safety(): void
    {
        config()->set('ai.vector_store.driver', InMemoryVectorStore::class);
        config()->set('ai.vector_store.collection', 'rag_eval');
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $admin = User::factory()->create(['id' => 1, 'is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->firstOrFail());

        $fixture = json_decode((string) file_get_contents(base_path('tests/Fixtures/AI/rag-eval/v1.json')), true);

        $report = $this->app->make(RagEvaluationService::class)->evaluate($fixture);

        $this->assertSame('v1', $report['version']);
        $this->assertSame(2, $report['case_count']);
        $this->assertGreaterThan(0.0, $report['recall']);
        $this->assertGreaterThan(0.0, $report['citation_correctness']);
        $this->assertSame(1.0, $report['injection_safety']);
        $this->assertCount(2, $report['cases']);
    }
}
