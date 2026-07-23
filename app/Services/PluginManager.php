<?php

namespace App\Services;

use App\Models\Plugin;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class PluginManager
{
    public function scanAndSync()
    {
        $pluginsDir = base_path('plugins');
        if (!File::exists($pluginsDir)) {
            File::makeDirectory($pluginsDir);
            return;
        }

        $directories = File::directories($pluginsDir);
        $scannedNamespaces = [];

        foreach ($directories as $dir) {
            $pluginJsonPath = $dir . '/plugin.json';
            
            if (File::exists($pluginJsonPath)) {
                $info = json_decode(File::get($pluginJsonPath), true);

                if (! is_array($info)) {
                    Log::warning("Invalid plugin manifest: {$pluginJsonPath}");
                    continue;
                }

                if (isset($info['namespace'])) {
                    $namespace = $info['namespace'];
                    $scannedNamespaces[] = $namespace;

                    Plugin::updateOrCreate(
                        ['namespace' => $namespace],
                        [
                            'name' => $info['name'] ?? $namespace,
                            'version' => $info['version'] ?? '1.0.0'
                        ]
                    );
                }
            }
        }

        // Deactivate plugins that are no longer present in the directory
        Plugin::whereNotIn('namespace', $scannedNamespaces)->update(['is_active' => false]);
    }

    public function activate($id)
    {
        $plugin = Plugin::findOrFail($id);
        $plugin->update(['is_active' => true]);
    }

    public function deactivate($id)
    {
        $plugin = Plugin::findOrFail($id);
        $plugin->update(['is_active' => false]);
    }
}
