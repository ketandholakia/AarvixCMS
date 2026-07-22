@extends('layouts.admin')

@section('header', 'Menu Builder: ' . $menu->name)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="menuBuilder({{ $menu->id }}, {{ $menu->items->toJson() }})">
    
    <!-- Add Items Panel -->
    <div class="space-y-6">
        <!-- Add Page -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Add Page</h4>
            <div class="space-y-3">
                <select x-model="newItem.page_id" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100">
                    <option value="">Select a Page...</option>
                    @foreach($pages as $page)
                        <option value="{{ $page->id }}" data-title="{{ $page->title }}">{{ $page->title }}</option>
                    @endforeach
                </select>
                <button @click="addPage()" :disabled="!newItem.page_id" class="w-full px-4 py-2 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 rounded-xl hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors text-sm font-medium disabled:opacity-50">
                    Add to Menu
                </button>
            </div>
        </div>

        <!-- Add Category -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Add Category</h4>
            <div class="space-y-3">
                <select x-model="newItem.category_id" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100">
                    <option value="">Select a Category...</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" data-title="{{ $category->name }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <button @click="addCategory()" :disabled="!newItem.category_id" class="w-full px-4 py-2 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 rounded-xl hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors text-sm font-medium disabled:opacity-50">
                    Add to Menu
                </button>
            </div>
        </div>

        <!-- Add Custom Link -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Add Custom Link</h4>
            <div class="space-y-3">
                <input type="text" x-model="newItem.custom_url" placeholder="URL (e.g. https://google.com)" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                <input type="text" x-model="newItem.custom_title" placeholder="Link Text" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                <button @click="addCustom()" :disabled="!newItem.custom_url || !newItem.custom_title" class="w-full px-4 py-2 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 rounded-xl hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors text-sm font-medium disabled:opacity-50">
                    Add to Menu
                </button>
            </div>
        </div>
    </div>

    <!-- Menu Structure -->
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
                <h3 class="font-medium text-gray-900 dark:text-white">Menu Structure</h3>
                <span x-show="isSaving" class="text-sm text-indigo-600 dark:text-indigo-400">Saving...</span>
            </div>
            
            <div class="p-6">
                <!-- Using a simple nested structure; for a real production CMS you'd integrate nested SortableJS -->
                <p x-show="items.length === 0" class="text-gray-500 text-sm text-center py-4">No items in this menu yet.</p>
                
                <div class="space-y-2" id="menu-items-container">
                    <template x-for="(item, index) in items" :key="item.id">
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                            <div>
                                <span class="font-medium text-gray-900 dark:text-white" x-text="item.title"></span>
                                <span class="text-xs text-gray-500 ml-2" x-text="item.linkable_type ? item.linkable_type.split('\\').pop() : 'Custom'"></span>
                            </div>
                            <button @click="deleteItem(item.id, index)" class="text-red-500 hover:text-red-700 text-sm">Remove</button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function menuBuilder(menuId, initialItems) {
    return {
        menuId: menuId,
        items: initialItems,
        isSaving: false,
        newItem: {
            page_id: '',
            category_id: '',
            custom_url: '',
            custom_title: ''
        },
        
        async postItem(data) {
            this.isSaving = true;
            try {
                const response = await fetch(`/admin/menus/${this.menuId}/items`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                if (response.ok) {
                    const result = await response.json();
                    this.items.push(result.item);
                    // Reset fields
                    this.newItem.page_id = '';
                    this.newItem.category_id = '';
                    this.newItem.custom_url = '';
                    this.newItem.custom_title = '';
                }
            } catch (e) {
                console.error(e);
                alert('Error saving menu item.');
            }
            this.isSaving = false;
        },

        addPage() {
            if (!this.newItem.page_id) return;
            const select = document.querySelector('select[x-model="newItem.page_id"]');
            const title = select.options[select.selectedIndex].text;
            
            this.postItem({
                title: title,
                linkable_type: 'App\\Models\\Page',
                linkable_id: this.newItem.page_id
            });
        },

        addCategory() {
            if (!this.newItem.category_id) return;
            const select = document.querySelector('select[x-model="newItem.category_id"]');
            const title = select.options[select.selectedIndex].text;
            
            this.postItem({
                title: title,
                linkable_type: 'App\\Models\\Category',
                linkable_id: this.newItem.category_id
            });
        },

        addCustom() {
            if (!this.newItem.custom_url || !this.newItem.custom_title) return;
            this.postItem({
                title: this.newItem.custom_title,
                url: this.newItem.custom_url
            });
        },

        async deleteItem(id, index) {
            if (!confirm('Remove this item?')) return;
            
            this.isSaving = true;
            try {
                const response = await fetch(`/admin/menus/items/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    this.items.splice(index, 1);
                }
            } catch (e) {
                console.error(e);
                alert('Error removing menu item.');
            }
            this.isSaving = false;
        }
    }
}
</script>
@endsection
