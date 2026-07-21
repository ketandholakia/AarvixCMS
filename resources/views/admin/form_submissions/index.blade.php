@extends('layouts.admin')

@section('header')
    Submissions for Form
@endsection

@section('content')
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">All Submissions</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Form</th>
                        <th class="px-4 py-3">Data</th>
                        <th class="px-4 py-3">Submitted At</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $submission)
                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">#{{ $submission->id }}</td>
                            <td class="px-4 py-3">{{ optional($submission->form)->name ?? 'Deleted Form' }}</td>
                            <td class="px-4 py-3">
                                <div class="space-y-1 max-w-sm">
                                    @foreach($submission->data as $key => $value)
                                        <div class="text-xs truncate">
                                            <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $key }}:</span> 
                                            {{ is_array($value) ? json_encode($value) : $value }}
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ $submission->created_at->format('M d, Y h:ia') }}</td>
                            <td class="px-4 py-3 text-right">
                                <form action="{{ route('admin.form_submissions.destroy', $submission->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this submission?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                No submissions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $records->links() }}
        </div>
    </div>
</div>
@endsection
