<?php

namespace Tests\Feature\Admin;

use App\Models\Plugin;
use App\Services\PluginManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PluginManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_skips_malformed_plugin_manifests(): void
    {
        $pluginDir = base_path('plugins/TestBadManifest');
        $manifestPath = $pluginDir . '/plugin.json';

        File::ensureDirectoryExists($pluginDir);
        File::put($manifestPath, '{invalid json');

        try {
            app(PluginManager::class)->scanAndSync();

            $this->assertDatabaseMissing('plugins', [
                'namespace' => 'TestBadManifest',
            ]);
        } finally {
            File::delete($manifestPath);
            File::deleteDirectory($pluginDir);
        }
    }
}
