<?php

namespace App\Traits;

use App\Models\Role;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Cache;

trait HasPermissions
{
    /**
     * A user has many roles.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Check if user has a specific permission (ADR-002 caching).
     */
    public function hasPermission(string $permissionName): bool
    {
        // Use the PermissionService to retrieve cached permissions
        $permissions = app(PermissionService::class)->getUserPermissions($this);

        return in_array($permissionName, $permissions);
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $roleName): bool
    {
        $roles = Cache::remember(
            "user:{$this->id}:roles",
            now()->addHours(24),
            function () {
                return $this->roles()->pluck('name')->toArray();
            }
        );

        return in_array($roleName, $roles);
    }

    /**
     * Clear the cached roles and permissions for this user.
     */
    public function clearPermissionCache(): void
    {
        Cache::forget("user:{$this->id}:permissions");
        Cache::forget("user:{$this->id}:roles");
    }
}
