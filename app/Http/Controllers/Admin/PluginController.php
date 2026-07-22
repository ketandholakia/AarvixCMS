<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Services\PluginManager;
use Illuminate\Http\Request;

class PluginController extends Controller
{
    protected $pluginManager;

    public function __construct(PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    public function index()
    {
        // Rescan directory and sync DB on every page load
        $this->pluginManager->scanAndSync();

        $plugins = Plugin::all();
        return view('admin.plugins.index', compact('plugins'));
    }

    public function toggle(Request $request, $id)
    {
        $plugin = Plugin::findOrFail($id);

        if ($plugin->is_active) {
            $this->pluginManager->deactivate($id);
            $msg = 'Plugin deactivated successfully.';
        } else {
            $this->pluginManager->activate($id);
            $msg = 'Plugin activated successfully.';
        }

        return redirect()->back()->with('success', $msg);
    }
}
