<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles')->latest();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $records = $query->paginate(20)->withQueryString();

        return view('admin.users.index', compact('records'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        $record = new User;
        return view('admin.users.form', compact('record', 'roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'password'  => ['required', Password::defaults()],
            'is_active' => ['boolean'],
            'roles'     => ['array'],
            'roles.*'   => ['exists:roles,id'],
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        if (!empty($data['roles'])) {
            $user->roles()->sync($data['roles']);
            $user->clearPermissionCache();
        }

        app(ActivityService::class)->log(auth()->user(), 'created', $user);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(string $id)
    {
        $record = User::with('roles')->findOrFail($id);
        $roles = Role::orderBy('name')->get();
        return view('admin.users.form', compact('record', 'roles'));
    }

    public function update(Request $request, string $id)
    {
        $record = User::findOrFail($id);

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'unique:users,email,' . $record->id],
            'password'  => ['nullable', Password::defaults()],
            'is_active' => ['boolean'],
            'roles'     => ['array'],
            'roles.*'   => ['exists:roles,id'],
        ]);

        $record->name      = $data['name'];
        $record->email     = $data['email'];
        $record->is_active = $request->boolean('is_active', true);
        if (!empty($data['password'])) {
            $record->password = Hash::make($data['password']);
        }
        $record->save();

        $record->roles()->sync($request->input('roles', []));
        $record->clearPermissionCache();

        app(ActivityService::class)->log(auth()->user(), 'updated', $record);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(string $id)
    {
        $record = User::findOrFail($id);

        if ($record->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        app(ActivityService::class)->log(auth()->user(), 'deleted', $record);
        $record->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
