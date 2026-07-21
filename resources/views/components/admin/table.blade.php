@props(['headers' => [], 'records' => null])

<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50/50 dark:bg-gray-800/50 text-gray-900 dark:text-white font-medium border-b border-gray-200 dark:border-gray-800">
                <tr>
                    @foreach($headers as $header)
                        <th scope="col" class="px-6 py-4">{{ $header }}</th>
                    @endforeach
                    @if(isset($actions))
                        <th scope="col" class="px-6 py-4 text-right">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                {{ $slot }}
            </tbody>
        </table>
    </div>
    
    @if($records && method_exists($records, 'links'))
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50/30 dark:bg-gray-900/30">
            {{ $records->links() }}
        </div>
    @endif
</div>
