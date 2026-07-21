<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_has_author_and_category(): void
    {
        $user = User::factory()->create(['name' => 'Author Name']);
        $category = Category::factory()->create(['name' => 'Tech']);
        
        $post = Post::factory()->create([
            'author_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->assertEquals('Author Name', $post->author->name);
        $this->assertEquals('Tech', $post->category->name);
    }

    public function test_page_has_author(): void
    {
        $user = User::factory()->create(['name' => 'Admin Name']);
        
        $page = Page::factory()->create([
            'author_id' => $user->id,
        ]);

        $this->assertEquals('Admin Name', $page->author->name);
    }
}
