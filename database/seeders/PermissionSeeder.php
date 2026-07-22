<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Users
            'view_users', 'create_users', 'edit_users', 'delete_users', 'manage_users',
            // Roles & Permissions
            'view_roles', 'edit_roles',
            // Settings
            'manage_settings',
            // Posts
            'view_posts', 'create_posts', 'edit_posts', 'delete_posts', 'publish_posts',
            // Pages
            'view_pages', 'create_pages', 'edit_pages', 'delete_pages', 'publish_pages',
            // Categories
            'view_categories', 'create_categories', 'edit_categories', 'delete_categories',
            // Forms (Phase 5)
            'view_forms', 'manage_forms',
            // Menus
            'manage_menus',
            // API
            'api.read', 'api.write',
        ];

        foreach ($permissions as $permission) {
            \App\Models\Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
