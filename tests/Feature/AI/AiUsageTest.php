<?php

namespace Tests\Feature\AI;

use App\AI\DTOs\AiRequestData;
use App\AI\Enums\AiStatus;
use App\AI\Exceptions\AiRateLimitException;
use App\AI\Exceptions\AiTimeoutException;
use App\AI\Providers\FakeAiProvider;
use App\AI\Services\AiManager;
use App\Models\AiRequest;
use App\Models\AiUsageDaily;
use App\Models\Role;
use App\Services\SettingService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsageTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach(Role::where('name', 'Admin')->first());

        return $user;
    }

    public function test_ai_manager_logs_requests_and_daily_usage(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);
        config()->set('ai.limits.requests_per_minute', 10);
        config()->set('ai.limits.daily_token_cap', 1000);
        config()->set('ai.limits.daily_cost_cap', '10.00');
        config()->set('ai.limits.monthly_cost_cap', '50.00');

        $user = $this->admin();
        $this->actingAs($user);

        /** @var AiManager $manager */
        $manager = $this->app->make(AiManager::class);
        $result = $manager->generate(new AiRequestData(
            input: ['prompt' => 'Write a short intro.'],
            feature: 'writer',
            model: 'fake-writer',
        ));

        $this->assertSame(AiStatus::Succeeded, $result->status);
        $this->assertDatabaseCount('ai_requests', 1);
        $this->assertDatabaseCount('ai_usage_daily', 1);

        $request = AiRequest::query()->first();
        $this->assertNotNull($request);
        $this->assertSame('writer', $request->feature);
        $this->assertSame('succeeded', $request->status);

        $usage = AiUsageDaily::query()->first();
        $this->assertNotNull($usage);
        $this->assertSame(1, $usage->requests_count);
        $this->assertGreaterThan(0, $usage->total_tokens);
    }

    public function test_ai_manager_enforces_request_rate_limits(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);
        config()->set('ai.limits.requests_per_minute', 1);
        config()->set('ai.limits.daily_token_cap', 1000);
        config()->set('ai.limits.daily_cost_cap', '10.00');
        config()->set('ai.limits.monthly_cost_cap', '50.00');

        $this->actingAs($this->admin());
        $manager = $this->app->make(AiManager::class);

        $manager->generate(new AiRequestData(
            input: ['prompt' => 'First request'],
            feature: 'writer',
            model: 'fake-writer',
        ));

        $this->expectException(AiRateLimitException::class);

        $manager->generate(new AiRequestData(
            input: ['prompt' => 'Second request'],
            feature: 'writer',
            model: 'fake-writer',
        ));
    }

    public function test_global_ai_kill_switch_blocks_requests(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.default_provider', 'fake');
        config()->set('ai.providers.fake.driver', FakeAiProvider::class);
        app(SettingService::class)->set('ai.enabled', false, 'ai', 'boolean');

        $this->actingAs($this->admin());
        $manager = $this->app->make(AiManager::class);

        $this->expectException(AiTimeoutException::class);

        $manager->generate(new AiRequestData(
            input: ['prompt' => 'This should be blocked'],
            feature: 'writer',
            model: 'fake-writer',
        ));
    }
}
