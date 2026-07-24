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

    public function test_admin_settings_page_uses_persisted_ai_toggle(): void
    {
        $admin = $this->setUpAdmin();

        config()->set('ai.enabled', true);
        app(SettingService::class)->set('ai.enabled', false, 'ai', 'boolean');

        $response = $this->actingAs($admin)->get(route('admin.settings.index'));

        $response->assertStatus(200);

        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/name="ai_enabled"[^>]*>/', $content, 'AI enabled checkbox was not rendered.');
        preg_match('/name="ai_enabled"[^>]*>/', $content, $matches);

        $this->assertNotEmpty($matches);
        $this->assertStringNotContainsString('checked', $matches[0]);
    }

    public function test_admin_can_update_settings(): void
    {
        $admin = $this->setUpAdmin();

        $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'site_name' => 'New Awesome Site',
            'social_twitter' => 'https://twitter.com/awesomesite',
            'ai_enabled' => 1,
            'ai_default_provider' => 'gemini',
            'ai_fallback_provider' => 'ollama',
            'ai_writer_enabled' => 1,
            'ai_chat_enabled' => 0,
            'ai_image_enabled' => 1,
            'ai_writer_model' => 'gemini-2.5-flash',
            'ai_chat_model' => 'gemini-2.5-flash',
            'ai_vision_model' => 'gemini-2.5-flash',
            'ai_image_model' => 'imagen-4.0-generate-preview',
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
        $this->assertSame('gemini', app(SettingService::class)->get('ai.default_provider'));
        $this->assertSame('ollama', app(SettingService::class)->get('ai.fallback_provider'));
        $this->assertFalse(app(SettingService::class)->get('ai.chat.enabled', true));
        $this->assertSame('gemini-2.5-flash', app(SettingService::class)->get('ai.models.writer.model'));
        $this->assertSame('gemini-2.5-flash', app(SettingService::class)->get('ai.models.chat.model'));
        $this->assertSame('gemini-2.5-flash', app(SettingService::class)->get('ai.models.vision.model'));
        $this->assertSame('imagen-4.0-generate-preview', app(SettingService::class)->get('ai.models.image.model'));
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
