<?php

namespace Tests\Feature\Admin;

use App\Models\ContentType;
use App\Models\Entry;
use App\Models\Role;
use App\Models\User;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryTest extends TestCase
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
        ], $overrides));
    }

    protected function makeEntry(ContentType $type, array $overrides = []): Entry
    {
        return Entry::factory()->create(array_merge([
            'content_type_id' => $type->id,
            'status'          => 'draft',
        ], $overrides));
    }

    // ─── Index ─────────────────────────────────────────────────────────────────

    public function test_admin_can_view_entries_index_for_a_type(): void
    {
        $type = $this->makeContentType();
        $this->makeEntry($type, ['title' => 'My Portfolio Piece']);

        $response = $this->actingAs($this->getAdmin())
            ->get(route('admin.entries.index', ['type' => $type->slug]));

        $response->assertStatus(200);
        $response->assertSee('My Portfolio Piece');
    }

    public function test_entries_are_scoped_to_their_type(): void
    {
        $type1 = $this->makeContentType(['slug' => 'portfolio', 'name' => 'Portfolio']);
        $type2 = $this->makeContentType(['slug' => 'events',    'name' => 'Events']);

        $this->makeEntry($type1, ['title' => 'Portfolio Entry']);
        $this->makeEntry($type2, ['title' => 'Event Entry']);

        $response = $this->actingAs($this->getAdmin())
            ->get(route('admin.entries.index', ['type' => 'portfolio']));

        $response->assertSee('Portfolio Entry');
        $response->assertDontSee('Event Entry');
    }

    public function test_index_returns_404_for_unknown_type(): void
    {
        $response = $this->actingAs($this->getAdmin())
            ->get(route('admin.entries.index', ['type' => 'nonexistent']));

        $response->assertStatus(404);
    }

    // ─── Store ─────────────────────────────────────────────────────────────────

    public function test_admin_can_create_an_entry(): void
    {
        $type = $this->makeContentType();
        $body = json_encode([
            'blocks' => [
                ['type' => 'paragraph', 'data' => ['text' => 'ACME entry body']],
            ],
        ]);

        $response = $this->actingAs($this->getAdmin())
            ->post(route('admin.entries.store', ['type' => $type->slug]), [
                'title'  => 'ACME Corp Branding',
                'slug'   => 'acme-branding',
                'body'   => $body,
                'status' => 'draft',
            ]);

        $response->assertRedirect(route('admin.entries.index', ['type' => $type->slug]));
        $this->assertDatabaseHas('entries', [
            'title'           => 'ACME Corp Branding',
            'slug'            => 'acme-branding',
            'body'            => $body,
            'content_type_id' => $type->id,
        ]);
    }

    public function test_entry_requires_a_title(): void
    {
        $type = $this->makeContentType();

        $response = $this->actingAs($this->getAdmin())
            ->post(route('admin.entries.store', ['type' => $type->slug]), [
                'status' => 'draft',
            ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_slug_is_unique_per_content_type(): void
    {
        $type = $this->makeContentType();
        $this->makeEntry($type, ['slug' => 'existing-slug']);

        $response = $this->actingAs($this->getAdmin())
            ->post(route('admin.entries.store', ['type' => $type->slug]), [
                'title'  => 'Duplicate Slug Entry',
                'slug'   => 'existing-slug',
                'status' => 'draft',
            ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_same_slug_can_exist_across_different_types(): void
    {
        $type1 = $this->makeContentType(['slug' => 'portfolio', 'name' => 'Portfolio']);
        $type2 = $this->makeContentType(['slug' => 'events',    'name' => 'Events']);
        $this->makeEntry($type1, ['slug' => 'shared-slug']);

        // Same slug in a different type should be fine
        $response = $this->actingAs($this->getAdmin())
            ->post(route('admin.entries.store', ['type' => $type2->slug]), [
                'title'  => 'Event with Shared Slug',
                'slug'   => 'shared-slug',
                'status' => 'draft',
            ]);

        $response->assertRedirect(route('admin.entries.index', ['type' => $type2->slug]));
        $this->assertDatabaseCount('entries', 2);
    }

    // ─── Custom Fields ─────────────────────────────────────────────────────────

    public function test_custom_fields_are_stored_as_json(): void
    {
        $type = $this->makeContentType([
            'fields_schema' => [
                ['key' => 'client', 'label' => 'Client', 'type' => 'text', 'required' => false],
                ['key' => 'year',   'label' => 'Year',   'type' => 'number', 'required' => false],
            ],
        ]);

        $response = $this->actingAs($this->getAdmin())
            ->post(route('admin.entries.store', ['type' => $type->slug]), [
                'title'         => 'Branding Project',
                'status'        => 'draft',
                'custom_fields' => [
                    'client' => 'Globex Corp',
                    'year'   => '2024',
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();


        $entry = Entry::first();
        $this->assertEquals('Globex Corp', $entry->getCustomField('client'));
        $this->assertEquals('2024',        $entry->getCustomField('year'));
    }

    // ─── Update ────────────────────────────────────────────────────────────────

    public function test_admin_can_update_an_entry(): void
    {
        $type  = $this->makeContentType();
        $entry = $this->makeEntry($type, ['title' => 'Old Title', 'slug' => 'old-slug']);

        $response = $this->actingAs($this->getAdmin())
            ->put(route('admin.entries.update', ['type' => $type->slug, 'entry' => $entry->id]), [
                'title'  => 'Updated Title',
                'slug'   => 'old-slug',
                'status' => 'published',
            ]);

        $response->assertRedirect(route('admin.entries.index', ['type' => $type->slug]));
        $this->assertDatabaseHas('entries', ['id' => $entry->id, 'title' => 'Updated Title', 'status' => 'published']);
    }

    // ─── Delete ────────────────────────────────────────────────────────────────

    public function test_admin_can_delete_an_entry(): void
    {
        $type  = $this->makeContentType();
        $entry = $this->makeEntry($type);

        $response = $this->actingAs($this->getAdmin())
            ->delete(route('admin.entries.destroy', ['type' => $type->slug, 'entry' => $entry->id]));

        $response->assertRedirect(route('admin.entries.index', ['type' => $type->slug]));
        $this->assertSoftDeleted('entries', ['id' => $entry->id]);
    }

    // ─── Cache Invalidation ────────────────────────────────────────────────────

    public function test_saving_an_entry_clears_its_frontend_cache(): void
    {
        $type  = $this->makeContentType(['slug' => 'portfolio']);
        $entry = $this->makeEntry($type, ['slug' => 'my-project', 'status' => 'published']);

        $cacheKey = 'page_cache_' . md5(url('/portfolio/my-project'));

        // Seed a fake cache entry
        \Illuminate\Support\Facades\Cache::put($cacheKey, '<html>old</html>', 300);
        $this->assertNotNull(\Illuminate\Support\Facades\Cache::get($cacheKey));

        // Saving the entry should invalidate it
        $entry->title = 'Updated';
        $entry->save();

        $this->assertNull(\Illuminate\Support\Facades\Cache::get($cacheKey));
    }
}
