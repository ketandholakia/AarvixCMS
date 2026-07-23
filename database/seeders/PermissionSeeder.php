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
            // Tags
            'view_tags', 'create_tags', 'edit_tags', 'delete_tags',
            // Forms (Phase 5)
            'view_forms', 'manage_forms',
            // Menus
            'manage_menus',
            // Media
            'manage_media',
            // Webhooks
            'manage_webhooks',
            // Plugins & Themes
            'manage_plugins', 'manage_themes',
            // Content Types (the registry itself — individual type permissions are auto-seeded at creation)
            'manage_content_types',
            // Comments
            'manage_comments',
            // Subscribers
            'manage_subscribers',
            // API tokens (issuing personal access tokens, distinct from api.read/api.write scopes below)
            'manage_api_tokens',
            // Revisions (viewing/restoring content history)
            'manage_revisions',
            // Subscriptions (Stripe/Cashier admin view)
            'view_subscriptions',
            // API
            'api.read', 'api.write',
            // AI
            'use_ai_writer', 'use_ai_image', 'use_ai_chat',
            'manage_ai_prompts', 'manage_ai_providers', 'view_ai_usage', 'manage_ai_workflows',
        ];

        foreach ($permissions as $permission) {
            \App\Models\Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
