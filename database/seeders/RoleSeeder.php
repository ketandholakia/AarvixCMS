<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allPermissions = \App\Models\Permission::all();

        // 1. Admin gets everything
        $admin = \App\Models\Role::firstOrCreate(['name' => 'Admin']);
        $admin->permissions()->sync($allPermissions);

        // 2. Editor gets posts, categories, and forms (no settings/users/roles)
        $editor = \App\Models\Role::firstOrCreate(['name' => 'Editor']);
        $editor->permissions()->sync(
            $allPermissions->whereIn('name', [
                'view_posts', 'create_posts', 'edit_posts', 'delete_posts', 'publish_posts',
                'view_categories', 'create_categories', 'edit_categories', 'delete_categories',
                'view_tags', 'create_tags', 'edit_tags', 'delete_tags',
                'view_forms', 'manage_forms',
                'manage_media', 'manage_menus', 'manage_comments', 'manage_revisions',
            ])
        );

        // 3. Author gets basic post management (no publish, no categories)
        $author = \App\Models\Role::firstOrCreate(['name' => 'Author']);
        $author->permissions()->sync(
            $allPermissions->whereIn('name', [
                'view_posts', 'create_posts', 'edit_posts'
            ])
        );
    }
}
