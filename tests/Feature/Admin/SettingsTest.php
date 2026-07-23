<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Role;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUpAdmin(): User
    {
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach(Role::where('name', 'Admin')->first());
        return $admin;
    }

    public function test_admin_can_view_settings(): void
    {
        $admin = $this->setUpAdmin();

        $response = $this->actingAs($admin)->get(route('admin.settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Site Settings');
        $response->assertSee('AI Agents');
    }

    public function test_admin_can_update_settings(): void
    {
        $admin = $this->setUpAdmin();

        $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'site_name' => 'New Awesome Site',
            'social_twitter' => 'https://twitter.com/awesomesite',
            'ai_enabled' => 1,
            'ai_writer_enabled' => 1,
            'ai_chat_enabled' => 0,
            'ai_image_enabled' => 1,
            'ai_agent_seo_enabled' => 1,
            'ai_agent_marketing_enabled' => 0,
            'ai_agent_translation_enabled' => 1,
            'ai_agent_documentation_enabled' => 0,
            'ai_agent_support_enabled' => 1,
            'ai_agent_seo_primary_model' => 'gpt-4.1',
            'ai_agent_seo_fallback_model' => 'gpt-4.1-mini',
            'ai_agent_seo_temperature' => '0.3',
            'ai_agent_seo_max_tokens' => '2048',
            'ai_agent_seo_max_cost' => '0.75',
            'ai_agent_seo_max_steps' => '5',
            'ai_agent_seo_max_seconds' => '90',
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        
        $this->assertDatabaseHas('settings', [
            'key' => 'site_name',
            'value' => 'New Awesome Site',
        ]);
        
        $this->assertDatabaseHas('settings', [
            'key' => 'social_twitter',
            'value' => 'https://twitter.com/awesomesite',
        ]);

        $this->assertSame('New Awesome Site', app(SettingService::class)->get('site_name'));
        $this->assertSame('https://twitter.com/awesomesite', Setting::get('social_twitter'));
        $this->assertTrue(app(SettingService::class)->get('ai.enabled', false));
        $this->assertFalse(app(SettingService::class)->get('ai.chat.enabled', true));
        $this->assertTrue(app(SettingService::class)->get('ai.agents.seo.enabled', false));
        $this->assertFalse(app(SettingService::class)->get('ai.agents.marketing.enabled', true));
        $this->assertTrue(app(SettingService::class)->get('ai.agents.translation.enabled', false));
        $this->assertFalse(app(SettingService::class)->get('ai.agents.documentation.enabled', true));
        $this->assertTrue(app(SettingService::class)->get('ai.agents.support.enabled', false));
        $this->assertSame('gpt-4.1', app(SettingService::class)->get('ai.agents.seo.primary_model'));
        $this->assertSame('gpt-4.1-mini', app(SettingService::class)->get('ai.agents.seo.fallback_model'));
        $this->assertSame('0.3', app(SettingService::class)->get('ai.agents.seo.temperature'));
        $this->assertSame('2048', app(SettingService::class)->get('ai.agents.seo.max_tokens'));
        $this->assertSame('0.75', app(SettingService::class)->get('ai.agents.seo.max_cost'));
        $this->assertSame('5', app(SettingService::class)->get('ai.agents.seo.max_steps'));
        $this->assertSame('90', app(SettingService::class)->get('ai.agents.seo.max_seconds'));
    }

    public function test_settings_service_rejects_unsupported_keys(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(SettingService::class)->set('unknown_key', 'value');
    }
}
