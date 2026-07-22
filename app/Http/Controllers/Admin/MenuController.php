<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MenuController extends Controller
{
    public function index()
    {
        $records = Menu::withCount('items')->latest()->paginate(20);
        return view('admin.menus.index', compact('records'));
    }

    public function create()
    {
        $record = new Menu;
        return view('admin.menus.form', compact('record'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|unique:menus,location|max:255',
        ]);

        $menu = Menu::create($data);
        Cache::forget('menus:all');

        return redirect()->route('admin.menus.builder', $menu->id)
            ->with('success', 'Menu created successfully. Now add some items.');
    }

    public function edit(string $id)
    {
        $record = Menu::findOrFail($id);
        return view('admin.menus.form', compact('record'));
    }

    public function update(Request $request, string $id)
    {
        $record = Menu::findOrFail($id);
        
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255|unique:menus,location,' . $record->id,
        ]);

        $record->update($data);
        Cache::forget('menus:all');

        return redirect()->route('admin.menus.index')->with('success', 'Menu updated successfully.');
    }

    public function destroy(string $id)
    {
        $record = Menu::findOrFail($id);
        $record->delete();
        Cache::forget('menus:all');

        return redirect()->route('admin.menus.index')->with('success', 'Menu deleted successfully.');
    }

    // Menu Builder View
    public function builder(string $id)
    {
        $menu = Menu::with(['items.linkable', 'items.children.linkable'])->findOrFail($id);
        $pages = Page::where('status', 'published')->orderBy('title')->get();
        $categories = Category::orderBy('name')->get();

        return view('admin.menus.builder', compact('menu', 'pages', 'categories'));
    }

    // Handle AJAX request to add a new item
    public function addItem(Request $request, string $id)
    {
        $menu = Menu::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'linkable_type' => 'nullable|string',
            'linkable_id' => 'nullable|integer',
            'target' => 'nullable|string|max:20',
        ]);

        // If linkable_type is provided (e.g. 'App\Models\Page')
        if (!empty($data['linkable_type']) && !empty($data['linkable_id'])) {
            $class = $data['linkable_type'];
            if (class_exists($class)) {
                $model = $class::find($data['linkable_id']);
                if ($model) {
                    $data['title'] = $data['title'] ?? ($model->title ?? $model->name);
                }
            }
        }

        $data['menu_id'] = $menu->id;
        $data['sort_order'] = MenuItem::where('menu_id', $menu->id)->max('sort_order') + 1;

        $item = MenuItem::create($data);
        Cache::forget('menus:all');

        return response()->json(['success' => true, 'item' => $item]);
    }

    // Handle AJAX request to reorder items
    public function reorder(Request $request, string $id)
    {
        $menu = Menu::findOrFail($id);
        $items = $request->input('items', []);

        // Flatten the hierarchy to update parent_id and sort_order
        $this->updateItemHierarchy($items, null);
        Cache::forget('menus:all');

        return response()->json(['success' => true]);
    }

    private function updateItemHierarchy(array $items, ?int $parentId)
    {
        foreach ($items as $index => $itemData) {
            MenuItem::where('id', $itemData['id'])->update([
                'parent_id' => $parentId,
                'sort_order' => $index,
            ]);

            if (!empty($itemData['children'])) {
                $this->updateItemHierarchy($itemData['children'], $itemData['id']);
            }
        }
    }

    // Handle AJAX request to delete an item
    public function destroyItem(string $id)
    {
        $item = MenuItem::findOrFail($id);
        $item->delete();
        Cache::forget('menus:all');

        return response()->json(['success' => true]);
    }
}
