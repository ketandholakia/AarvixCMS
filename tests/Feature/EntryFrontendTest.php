<?php

namespace Tests\Feature;

use App\Models\ContentType;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryFrontendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

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
            'status'          => 'published',
            'published_at'    => now()->subDay(),
        ], $overrides));
    }

    // ─── Happy Path ────────────────────────────────────────────────────────────

    public function test_published_entry_is_accessible_on_frontend(): void
    {
        $type  = $this->makeContentType();
        $entry = $this->makeEntry($type, ['title' => 'ACME Branding', 'slug' => 'acme-branding']);

        $response = $this->get("/{$type->slug}/{$entry->slug}");

        $response->assertStatus(200);
        $response->assertSee('ACME Branding');
    }

    // ─── Draft / Unpublished ───────────────────────────────────────────────────

    public function test_draft_entry_returns_404(): void
    {
        $type  = $this->makeContentType();
        $entry = $this->makeEntry($type, ['status' => 'draft']);

        $response = $this->get("/{$type->slug}/{$entry->slug}");

        $response->assertStatus(404);
    }

    public function test_archived_entry_returns_404(): void
    {
        $type  = $this->makeContentType();
        $entry = $this->makeEntry($type, ['status' => 'archived']);

        $response = $this->get("/{$type->slug}/{$entry->slug}");

        $response->assertStatus(404);
    }

    public function test_future_published_at_returns_404(): void
    {
        $type  = $this->makeContentType();
        $entry = $this->makeEntry($type, [
            'status'       => 'published',
            'published_at' => now()->addDay(),
        ]);

        $response = $this->get("/{$type->slug}/{$entry->slug}");

        $response->assertStatus(404);
    }

    // ─── Unknown Type / Slug ───────────────────────────────────────────────────

    public function test_unknown_type_slug_returns_404(): void
    {
        $response = $this->get('/completely-unknown-type/some-slug');

        $response->assertStatus(404);
    }

    public function test_inactive_content_type_returns_404(): void
    {
        $type  = $this->makeContentType(['is_active' => false]);
        $entry = $this->makeEntry($type);

        $response = $this->get("/{$type->slug}/{$entry->slug}");

        $response->assertStatus(404);
    }

    public function test_correct_type_wrong_slug_returns_404(): void
    {
        $type = $this->makeContentType();

        $response = $this->get("/{$type->slug}/does-not-exist");

        $response->assertStatus(404);
    }

    // ─── Custom Fields On Frontend ─────────────────────────────────────────────

    public function test_custom_field_values_are_rendered_on_frontend(): void
    {
        $type = $this->makeContentType([
            'fields_schema' => [
                ['key' => 'client', 'label' => 'Client Name', 'type' => 'text', 'required' => false],
            ],
        ]);

        $entry = $this->makeEntry($type, [
            'custom_fields' => ['client' => 'Wayne Enterprises'],
        ]);

        $response = $this->get("/{$type->slug}/{$entry->slug}");

        $response->assertStatus(200);
        $response->assertSee('Wayne Enterprises');
        $response->assertSee('Client Name');
    }

    // ─── Named Route ──────────────────────────────────────────────────────────

    public function test_entry_show_route_generates_correct_url(): void
    {
        $type  = $this->makeContentType(['slug' => 'portfolio']);
        $entry = $this->makeEntry($type, ['slug' => 'my-project']);

        $url = route('entry.show', ['type_slug' => 'portfolio', 'slug' => 'my-project']);

        $this->assertStringContainsString('/portfolio/my-project', $url);
    }

    // ─── Isolation Between Types ───────────────────────────────────────────────

    public function test_entry_is_not_accessible_under_wrong_type_prefix(): void
    {
        $type1 = $this->makeContentType(['slug' => 'portfolio', 'name' => 'Portfolio']);
        $type2 = $this->makeContentType(['slug' => 'events',    'name' => 'Events']);

        $entry = $this->makeEntry($type1, ['slug' => 'my-entry']);

        // Accessing type1's entry under type2's prefix should 404
        $response = $this->get("/events/my-entry");
        $response->assertStatus(404);
    }
}
