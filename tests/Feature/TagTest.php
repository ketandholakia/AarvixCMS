<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Page;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_can_be_attached_to_post_and_page(): void
    {
        $post = Post::factory()->create();
        $page = Page::factory()->create();
        $tag = Tag::factory()->create(['name' => 'Laravel']);

        $post->tags()->attach($tag);
        $page->tags()->attach($tag);

        $this->assertCount(1, $post->tags);
        $this->assertEquals('Laravel', $post->tags->first()->name);

        $this->assertCount(1, $page->tags);
        $this->assertEquals('Laravel', $page->tags->first()->name);

        $this->assertCount(1, $tag->posts);
        $this->assertCount(1, $tag->pages);
    }
}
