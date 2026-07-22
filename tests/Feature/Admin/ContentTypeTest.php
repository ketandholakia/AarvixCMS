<?php

namespace Tests\Feature\Admin;

use App\Models\ContentType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\ContentTypeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentTypeTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    protected function getAdmin(): User
    {
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $admin = User::factory()->create(['is_active' => true]);
        $adminRole = Role::where('name', 'Admin')->first();
        $admin->roles()->attach($adminRole);

        return $admin;
    }

    protected function makeContentType(array $overrides = []): ContentType
    {
        return ContentType::factory()->create(array_merge([
            'name'      => 'Portfolio',
            'slug'      => 'portfolio',
            'context'   => 'post',
            'is_active' => true,
            'is_system' => false,
        ], $overrides));
    }

    // ─── Index ─────────────────────────────────────────────────────────────────

    public function test_admin_can_view_content_types_index(): void
    {
        $this->makeContentType();

        $response = $this->actingAs($this->getAdmin())
            ->get(route('admin.content-types.index'));

        $response->assertStatus(200);
        $response->assertSee('Portfolio');
    }

    // ─── Create / Store ────────────────────────────────────────────────────────

    public function test_admin_can_create_a_content_type(): void
    {
        $response = $this->actingAs($this->getAdmin())
            ->post(route('admin.content-types.store'), [
                'name'      => 'Event',
                'slug'      => 'events',
                'context'   => 'post',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('admin.content-types.index'));
        $this->assertDatabaseHas('content_types', [
            'name' => 'Event',
            'slug' => 'events',
        ]);
    }

    public function test_creating_a_content_type_auto_seeds_four_permissions(): void
    {
        $this->actingAs($this->getAdmin())
            ->post(route('admin.content-types.store'), [
                'name'      => 'Recipe',
                'slug'      => 'recipes',
                'context'   => 'post',
                'is_active' => '1',
            ]);

        foreach (['view_recipes', 'create_recipes', 'edit_recipes', 'delete_recipes'] as $perm) {
            $this->assertDatabaseHas('permissions', ['name' => $perm]);
        }
    }

    public function test_slug_must_be_unique(): void
    {
        $this->makeContentType(['slug' => 'portfolio']);

        $response = $this->actingAs($this->getAdmin())
            ->post(route('admin.content-types.store'), [
                'name'    => 'Another',
                'slug'    => 'portfolio',
                'context' => 'post',
            ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_slug_must_be_lowercase_hyphenated(): void
    {
        $response = $this->actingAs($this->getAdmin())
            ->post(route('admin.content-types.store'), [
                'name'    => 'Bad Slug',
                'slug'    => 'Bad Slug!!',
                'context' => 'post',
            ]);

        $response->assertSessionHasErrors('slug');
    }

    // ─── Edit / Update ─────────────────────────────────────────────────────────

    public function test_admin_can_update_a_content_type(): void
    {
        $type = $this->makeContentType();

        $response = $this->actingAs($this->getAdmin())
            ->put(route('admin.content-types.update', $type->id), [
                'name'      => 'Portfolio (Updated)',
                'slug'      => 'portfolio',
                'context'   => 'post',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('admin.content-types.index'));
        $this->assertDatabaseHas('content_types', ['name' => 'Portfolio (Updated)']);
    }

    public function test_system_types_cannot_be_updated(): void
    {
        $type = $this->makeContentType(['is_system' => true]);

        $response = $this->actingAs($this->getAdmin())
            ->put(route('admin.content-types.update', $type->id), [
                'name'    => 'Hacked',
                'slug'    => 'hacked',
                'context' => 'post',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('content_types', ['name' => 'Hacked']);
    }

    // ─── Delete ────────────────────────────────────────────────────────────────

    public function test_admin_can_delete_a_content_type_without_entries(): void
    {
        $type = $this->makeContentType();

        $response = $this->actingAs($this->getAdmin())
            ->delete(route('admin.content-types.destroy', $type->id));

        $response->assertRedirect(route('admin.content-types.index'));
        $this->assertDatabaseMissing('content_types', ['id' => $type->id]);
    }

    public function test_cannot_delete_a_system_type(): void
    {
        $type = $this->makeContentType(['is_system' => true]);

        $response = $this->actingAs($this->getAdmin())
            ->delete(route('admin.content-types.destroy', $type->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('content_types', ['id' => $type->id]);
    }

    // ─── Field Schema ──────────────────────────────────────────────────────────

    public function test_field_schema_is_stored_as_json(): void
    {
        $type = $this->makeContentType();

        $this->actingAs($this->getAdmin())
            ->put(route('admin.content-types.save-schema', $type->id), [
                'fields_schema' => [
                    ['key' => 'client', 'label' => 'Client Name', 'type' => 'text', 'required' => '1'],
                    ['key' => 'year',   'label' => 'Year',        'type' => 'number', 'required' => '0'],
                ],
            ]);

        $type->refresh();
        $this->assertCount(2, $type->fields_schema);
        $this->assertEquals('client', $type->fields_schema[0]['key']);
    }

    public function test_field_key_must_be_lowercase_underscore(): void
    {
        $type = $this->makeContentType();

        $response = $this->actingAs($this->getAdmin())
            ->put(route('admin.content-types.save-schema', $type->id), [
                'fields_schema' => [
                    ['key' => 'Bad Key!', 'label' => 'Bad', 'type' => 'text'],
                ],
            ]);

        $response->assertSessionHasErrors('fields_schema.0.key');
    }

    // ─── Registry Cache ────────────────────────────────────────────────────────

    public function test_registry_is_invalidated_after_store(): void
    {
        $registry = app(ContentTypeRegistry::class);

        // Warm up the cache with an empty set
        $registry->all();

        $this->actingAs($this->getAdmin())
            ->post(route('admin.content-types.store'), [
                'name'      => 'Gallery',
                'slug'      => 'gallery',
                'context'   => 'post',
                'is_active' => '1',
            ]);

        // After store, registry should reflect the new type
        $this->assertNotNull($registry->find('gallery'));
    }
}
