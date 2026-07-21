<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads_with_published_posts(): void
    {
        $published = Post::factory()->create(['status' => 'published', 'title' => 'Visible Post']);
        $draft = Post::factory()->create(['status' => 'draft', 'title' => 'Hidden Post']);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('Visible Post');
        $response->assertDontSee('Hidden Post');
    }

    public function test_single_post_loads_if_published(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->get(route('post.show', $post->slug));
        
        $response->assertStatus(200);
        $response->assertSee($post->title);
    }

    public function test_single_post_returns_404_if_draft(): void
    {
        $post = Post::factory()->create(['status' => 'draft']);

        $response = $this->get(route('post.show', $post->slug));
        
        $response->assertStatus(404);
    }

    public function test_single_page_loads_if_published(): void
    {
        $page = Page::factory()->create(['status' => 'published']);

        $response = $this->get(route('page.show', $page->slug));
        
        $response->assertStatus(200);
        $response->assertSee($page->title);
    }

    public function test_blog_filtering_by_category(): void
    {
        $cat1 = \App\Models\Category::factory()->create(['name' => 'News']);
        $cat2 = \App\Models\Category::factory()->create(['name' => 'Updates']);

        Post::factory()->create(['status' => 'published', 'title' => 'News Post', 'category_id' => $cat1->id]);
        Post::factory()->create(['status' => 'published', 'title' => 'Update Post', 'category_id' => $cat2->id]);

        $response = $this->get(route('category.show', $cat1->slug));

        $response->assertStatus(200);
        $response->assertSee('News Post');
        $response->assertDontSee('Update Post');
    }

    public function test_page_caching_middleware_caches_guest_requests(): void
    {
        $page = Page::factory()->create(['status' => 'published', 'title' => 'Cached Title']);

        // First hit (not cached yet)
        $response1 = $this->get(route('page.show', $page->slug));
        $response1->assertStatus(200);
        $response1->assertSee('Cached Title');

        // Update title directly in DB (bypassing Eloquent events to test cache)
        \Illuminate\Support\Facades\DB::table('pages')->where('id', $page->id)->update(['title' => 'New Title']);

        // Second hit (should be served from cache)
        $response2 = $this->get(route('page.show', $page->slug));
        $response2->assertSee('Cached Title'); // Still sees old title
        $response2->assertDontSee('New Title');

        // Now save via eloquent to trigger our booted flush
        $page->refresh();
        $page->title = 'New Title 2';
        $page->save();

        // Third hit (cache was flushed, should see new title)
        $response3 = $this->get(route('page.show', $page->slug));
        $response3->assertSee('New Title 2');
        $response3->assertDontSee('Cached Title');
    }
}
