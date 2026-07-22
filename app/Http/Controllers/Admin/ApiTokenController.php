<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    public function index(Request $request)
    {
        $tokens = $request->user()->tokens;
        return view('admin.api_tokens.index', compact('tokens'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'required|array|min:1',
        ]);

        $abilities = $request->input('abilities', ['api.read']);

        $token = $request->user()->createToken($request->name, $abilities);

        return back()->with('success', 'API Token created successfully.')->with('plainTextToken', $token->plainTextToken);
    }

    public function destroy(Request $request, $id)
    {
        $request->user()->tokens()->where('id', $id)->delete();
        return back()->with('success', 'API Token deleted successfully.');
    }
}
