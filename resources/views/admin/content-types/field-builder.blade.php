@extends('layouts.admin')

@section('header', 'Field Builder: ' . $contentType->name)

@section('content')
<div x-data="fieldBuilder({{ json_encode($contentType->fields_schema ?? []) }})" class="max-w-3xl space-y-6">

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Custom Fields</h3>
                <p class="text-sm text-gray-500 mt-0.5">Define additional fields that editors fill in when creating <strong>{{ $contentType->name }}</strong> entries.</p>
            </div>
            <button @click="addField()" type="button"
                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Field
            </button>
        </div>

        <div class="space-y-3" x-show="fields.length > 0">
            <template x-for="(field, index) in fields" :key="index">
                <div class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700">
                    <!-- Drag handle -->
                    <div class="mt-2 text-gray-400 cursor-move flex-shrink-0">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm8 0a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM8 13.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm8 0a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM8 21a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm8 0a1.5 1.5 0 110-3 1.5 1.5 0 010 3z"/></svg>
                    </div>

                    <div class="flex-1 grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Field Key</label>
                            <input type="text" x-model="field.key" placeholder="e.g. client_name"
                                   pattern="[a-z_]+"
                                   class="w-full px-2 py-1.5 text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Label</label>
                            <input type="text" x-model="field.label" placeholder="e.g. Client Name"
                                   class="w-full px-2 py-1.5 text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Type</label>
                            <select x-model="field.type"
                                    class="w-full px-2 py-1.5 text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="text">Text</option>
                                <option value="textarea">Textarea</option>
                                <option value="number">Number</option>
                                <option value="url">URL</option>
                                <option value="email">Email</option>
                                <option value="date">Date</option>
                                <option value="select">Select (dropdown)</option>
                                <option value="checkbox">Checkbox (boolean)</option>
                                <option value="media">Media (image picker)</option>
                            </select>
                        </div>
                        <div x-show="field.type === 'select'">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Options (comma-separated)</label>
                            <input type="text" x-model="field.options" placeholder="Option A, Option B, Option C"
                                   class="w-full px-2 py-1.5 text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <div class="col-span-2 flex items-center gap-2">
                            <input type="checkbox" :id="'req_' + index" x-model="field.required"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <label :for="'req_' + index" class="text-xs text-gray-600 dark:text-gray-400">Required field</label>
                        </div>
                    </div>

                    <button @click="removeField(index)" type="button"
                            class="mt-1 text-gray-400 hover:text-red-500 transition-colors flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>
        </div>

        <div x-show="fields.length === 0" class="py-8 text-center text-gray-400 dark:text-gray-500 text-sm">
            No custom fields defined. Click "Add Field" to start building your schema.
        </div>
    </div>

    <!-- Save Form -->
    <form action="{{ route('admin.content-types.save-schema', $contentType->id) }}" method="POST" @submit="prepareSubmit">
        @csrf @method('PUT')
        <div id="schema-inputs"></div>
        <div class="flex items-center gap-3">
            <button type="submit"
                    class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition-colors">
                Save Field Schema
            </button>
            <a href="{{ route('admin.content-types.index') }}"
               class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
function fieldBuilder(initialFields) {
    return {
        fields: initialFields.length ? initialFields : [],

        addField() {
            this.fields.push({ key: '', label: '', type: 'text', required: false, options: '' });
        },

        removeField(index) {
            this.fields.splice(index, 1);
        },

        prepareSubmit(event) {
            const container = document.getElementById('schema-inputs');
            container.innerHTML = '';
            this.fields.forEach((field, i) => {
                Object.keys(field).forEach(k => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `fields_schema[${i}][${k}]`;
                    input.value = field[k] === true ? '1' : (field[k] === false ? '0' : (field[k] ?? ''));
                    container.appendChild(input);
                });
            });
        }
    };
}
</script>
@endsection
