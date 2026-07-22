<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index()
    {
        $webhooks = Webhook::orderBy('id', 'desc')->paginate(20);
        return view('admin.webhooks.index', compact('webhooks'));
    }

    public function create()
    {
        $record = new Webhook();
        return view('admin.webhooks.form', compact('record'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'secret' => 'nullable|string|max:255',
            'events' => 'nullable|array',
            'events.*' => 'string',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->has('is_active');

        Webhook::create($data);

        return redirect()->route('admin.webhooks.index')->with('success', 'Webhook created.');
    }

    public function edit(Webhook $webhook)
    {
        $record = $webhook;
        return view('admin.webhooks.form', compact('record'));
    }

    public function update(Request $request, Webhook $webhook)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'secret' => 'nullable|string|max:255',
            'events' => 'nullable|array',
            'events.*' => 'string',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->has('is_active');

        $webhook->update($data);

        return redirect()->route('admin.webhooks.index')->with('success', 'Webhook updated.');
    }

    public function destroy(Webhook $webhook)
    {
        $webhook->delete();
        return redirect()->route('admin.webhooks.index')->with('success', 'Webhook deleted.');
    }
}
