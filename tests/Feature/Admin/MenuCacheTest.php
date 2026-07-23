<?php

namespace Tests\Feature\Admin;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MenuCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_mutations_invalidate_frontend_cache_by_location(): void
    {
        $menu = Menu::create([
            'name' => 'Primary',
            'location' => 'primary',
        ]);

        Cache::put('menu:primary', 'stale', now()->addHour());

        $menu->update([
            'location' => 'main',
        ]);

        $this->assertFalse(Cache::has('menu:primary'));

        Cache::put('menu:main', 'stale', now()->addHour());

        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Home',
            'url' => '/',
            'sort_order' => 1,
        ]);

        $this->assertFalse(Cache::has('menu:main'));
    }
}
