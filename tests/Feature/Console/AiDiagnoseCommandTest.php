<?php

namespace Tests\Feature\Console;

use App\AI\Providers\FakeAiProvider;
use App\Models\AiRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ThrowingAiProvider;
use Tests\TestCase;

class AiDiagnoseCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach(Role::where('name', 'Admin')->first());

        return $user;
    }

    public function test_ai_diagnose_command_runs_a_minimal_generation_request(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);

        $this->artisan('ai:diagnose')
            ->expectsOutputToContain('AI diagnostic request')
            ->expectsOutputToContain('Provider: fake')
            ->expectsOutputToContain('Model: fake-writer')
            ->expectsOutputToContain('Rewritten draft: Diagnostics ping.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('ai_requests', 1);

        $request = AiRequest::query()->first();
        $this->assertNotNull($request);
        $this->assertSame('diagnostics', $request->feature);
        $this->assertSame('succeeded', $request->status);
        $this->assertSame('fake', $request->provider);
        $this->assertSame('fake-writer', $request->model);
    }

    public function test_ai_diagnose_command_reports_provider_failure_cleanly(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'throwing');
        config()->set('ai.providers.throwing.driver', ThrowingAiProvider::class);

        $this->artisan('ai:diagnose')
            ->expectsOutputToContain('AI diagnostic request')
            ->expectsOutputToContain('AI diagnostics failed: Provider is unavailable.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('ai_requests', 1);

        $request = AiRequest::query()->first();
        $this->assertNotNull($request);
        $this->assertSame('diagnostics', $request->feature);
        $this->assertSame('failed', $request->status);
        $this->assertSame('throwing', $request->provider);
    }
}
