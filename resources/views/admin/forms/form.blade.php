@php
    $action = isset($record) ? route('admin.forms.update', $record->id) : route('admin.forms.store');
    $method = isset($record) ? 'PUT' : 'POST';
@endphp

<form action="{{ $action }}" method="POST">
    @csrf
    @method($method)
    
    <div class="flex flex-col lg:flex-row gap-6 items-start">
        <!-- Main Form Column -->
        <div class="flex-1 w-full space-y-6">
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 space-y-6">
                <x-admin.form.input 
                    name="name" 
                    label="Form Name" 
                    :value="$record->name ?? ''" 
                    required 
                />

                <x-admin.form.input 
                    name="slug" 
                    label="Slug (URL identifier)" 
                    :value="$record->slug ?? ''" 
                    required 
                />

                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea 
                        name="description" 
                        rows="3" 
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >{{ old('description', $record->description ?? '') }}</textarea>
                </div>
            </div>

            <!-- Dynamic Fields Builder (Alpine.js) -->
            <div 
                x-data="formBuilder({{ json_encode(old('fields', $record->fields ?? [])) }})" 
                class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 space-y-6"
            >
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Form Fields</h3>
                    <button type="button" @click="addField" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                        + Add Field
                    </button>
                </div>

                <!-- Hidden input to store JSON for backend -->
                <input type="hidden" name="fields" :value="JSON.stringify(fields)">

                <div class="space-y-4" x-show="fields.length > 0">
                    <template x-for="(field, index) in fields" :key="index">
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg flex items-start gap-4">
                            
                            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Label</label>
                                    <input type="text" x-model="field.label" class="block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700" placeholder="e.g. First Name" @input="updateName(index)">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Internal Name</label>
                                    <input type="text" x-model="field.name" class="block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700" placeholder="e.g. first_name">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
                                    <select x-model="field.type" class="block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                        <option value="text">Text (Single Line)</option>
                                        <option value="email">Email</option>
                                        <option value="textarea">Textarea (Multi-line)</option>
                                        <option value="checkbox">Checkbox</option>
                                    </select>
                                </div>
                                <div class="col-span-1 md:col-span-3 flex items-center gap-2 mt-2">
                                    <input type="checkbox" x-model="field.required" :id="'req_'+index" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <label :for="'req_'+index" class="text-sm text-gray-700 dark:text-gray-300">Required Field</label>
                                </div>
                            </div>

                            <button type="button" @click="removeField(index)" class="mt-6 text-red-500 hover:text-red-700" title="Remove Field">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </template>
                </div>

                <div x-show="fields.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400 text-sm border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
                    No fields defined yet. Click "Add Field" to start building your form.
                </div>
            </div>
        </div>

        <!-- Sidebar Column -->
        <div class="w-full lg:w-80 space-y-6">
            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 space-y-4">
                <h4 class="font-medium text-gray-900 dark:text-white">Status</h4>
                
                <x-admin.form.select 
                    name="is_active" 
                    label="Active" 
                    :value="$record->is_active ?? 1" 
                    :options="[1 => 'Yes (Accepting Submissions)', 0 => 'No (Closed)']"
                />

                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Save Form
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('formBuilder', (initialFields) => ({
            fields: Array.isArray(initialFields) ? initialFields : [],
            
            addField() {
                this.fields.push({
                    label: '',
                    name: '',
                    type: 'text',
                    required: false
                });
            },
            
            removeField(index) {
                this.fields.splice(index, 1);
            },

            updateName(index) {
                // Auto-slugify the label into the internal name if it's empty
                if (this.fields[index].label && !this.fields[index].name) {
                    this.fields[index].name = this.fields[index].label
                        .toLowerCase()
                        .replace(/[^\w ]+/g, '')
                        .replace(/ +/g, '_');
                }
            }
        }));
    });
</script>
