<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_tree_cte_returns_hierarchical_data(): void
    {
        $root1 = Category::create(['name' => 'Root 1', 'sort_order' => 1]);
        $root2 = Category::create(['name' => 'Root 2', 'sort_order' => 2]);
        
        $child1 = Category::create(['name' => 'Child 1', 'parent_id' => $root1->id, 'sort_order' => 1]);
        $child2 = Category::create(['name' => 'Child 2', 'parent_id' => $root1->id, 'sort_order' => 2]);
        
        $grandchild = Category::create(['name' => 'Grandchild', 'parent_id' => $child1->id, 'sort_order' => 1]);

        // Get tree
        $tree = Category::tree();

        // Check depth and sorting
        $this->assertCount(5, $tree);
        
        // Due to path ordering (Root 1 -> Child 1 -> Grandchild -> Child 2 -> Root 2)
        $this->assertEquals('Root 1', $tree[0]->name);
        $this->assertEquals(0, $tree[0]->depth);

        $this->assertEquals('Child 1', $tree[1]->name);
        $this->assertEquals(1, $tree[1]->depth);

        $this->assertEquals('Grandchild', $tree[2]->name);
        $this->assertEquals(2, $tree[2]->depth);

        $this->assertEquals('Child 2', $tree[3]->name);
        $this->assertEquals(1, $tree[3]->depth);

        $this->assertEquals('Root 2', $tree[4]->name);
        $this->assertEquals(0, $tree[4]->depth);
    }

    public function test_category_tree_cache_invalidation_on_save(): void
    {
        Category::create(['name' => 'Root 1']);
        
        // Warm cache
        Category::tree();
        $this->assertTrue(Cache::has('categories:tree'));

        // Save invalidates cache
        Category::create(['name' => 'Root 2']);
        $this->assertFalse(Cache::has('categories:tree'));
    }

    public function test_category_tree_cache_invalidation_on_delete(): void
    {
        $cat = Category::create(['name' => 'Root 1']);
        
        // Warm cache
        Category::tree();
        $this->assertTrue(Cache::has('categories:tree'));

        // Delete invalidates cache
        $cat->delete();
        $this->assertFalse(Cache::has('categories:tree'));
    }
}
