<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    /**
     * Get the cached list of permissions for a user.
     * Caches per user based on ADR-002.
     */
    public function getUserPermissions(User $user): array
    {
        return Cache::remember(
            "user:{$user->id}:permissions",
            now()->addHours(24),
            function () use ($user) {
                // Eager load roles and their permissions to avoid N+1
                return $user->roles()->with('permissions')->get()
                    ->pluck('permissions')
                    ->flatten()
                    ->pluck('name')
                    ->unique()
                    ->toArray();
            }
        );
    }

    /**
     * Invalidate the permission cache for a specific user.
     * Call this when a user's roles are updated.
     */
    public function invalidateUserCache(User $user): void
    {
        Cache::forget("user:{$user->id}:permissions");
        Cache::forget("user:{$user->id}:roles");
    }

    /**
     * Invalidate the permission cache for all users in a specific role.
     * Call this when a role's permissions are updated.
     */
    public function invalidateRoleCache($role): void
    {
        // $role could be a Role model instance
        $role->users()->chunk(100, function ($users) {
            foreach ($users as $user) {
                $this->invalidateUserCache($user);
            }
        });
    }
}
