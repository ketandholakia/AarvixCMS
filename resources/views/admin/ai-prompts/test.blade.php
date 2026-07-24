@extends('layouts.admin')

@section('header', 'Prompt Tester')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $prompt->category }}</div>
                <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $prompt->title }}</h2>
                <div class="mt-2 font-mono text-sm text-gray-600 dark:text-gray-300">{{ $prompt->prompt_key }}</div>
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">{{ $prompt->description }}</p>
                <div class="mt-4 flex flex-wrap gap-2 text-xs">
                    <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 font-semibold text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">
                        Active version {{ $version->version_number }}
                    </span>
                    <span class="inline-flex rounded-full {{ $prompt->is_enabled ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }} px-2.5 py-1 font-semibold">
                        {{ $prompt->is_enabled ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
            </div>
            <a href="{{ route('admin.ai-prompts.show', $prompt) }}" class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Back to prompt</a>
        </div>
    </div>

    @if($errorMessage)
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="space-y-6">
            <form action="{{ route('admin.ai-prompts.test.run', $prompt) }}" method="POST" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-6">
                @csrf

                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Run Prompt</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Render the active prompt version and send it through the selected provider.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <x-admin.form.select
                        name="provider"
                        label="Provider"
                        :value="old('provider', $form['provider'] ?? 'fake')"
                        :options="$providerOptions"
                        required="true"
                        help="Uses the provider configured in AI settings by default."
                    />
                    <x-admin.form.input
                        name="model"
                        label="Model"
                        :value="old('model', $form['model'] ?? '')"
                        help="Leave blank to use the provider default model."
                    />
                </div>

                <x-admin.form.textarea
                    name="runtime_input"
                    label="Test input"
                    :value="old('runtime_input', $form['runtime_input'] ?? '')"
                    rows="5"
                    help="Use this as a freeform input value if the prompt template includes an `@{{input}}` placeholder."
                />

                <x-admin.form.textarea
                    name="variables_json"
                    label="Variables JSON"
                    :value="old('variables_json', $form['variables_json'] ?? '{}')"
                    rows="12"
                    help="Must include every placeholder used by the active version."
                />

                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Run prompt</button>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Placeholders</h3>
                <div class="mt-4 space-y-3 text-sm">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">System</div>
                        <div class="mt-1 font-mono text-gray-600 dark:text-gray-300">{{ implode(', ', $placeholders['system'] ?? []) ?: 'none' }}</div>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">User</div>
                        <div class="mt-1 font-mono text-gray-600 dark:text-gray-300">{{ implode(', ', $placeholders['user'] ?? []) ?: 'none' }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rendered Prompt</h3>
                @if($rendered)
                    <div class="mt-4 space-y-4">
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">System</div>
                            <pre class="mt-2 overflow-x-auto rounded-xl bg-gray-50 p-4 text-sm text-gray-800 dark:bg-gray-950 dark:text-gray-200">{{ $rendered['system'] }}</pre>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">User</div>
                            <pre class="mt-2 overflow-x-auto rounded-xl bg-gray-50 p-4 text-sm text-gray-800 dark:bg-gray-950 dark:text-gray-200">{{ $rendered['user'] ?? 'n/a' }}</pre>
                        </div>
                    </div>
                @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Run the prompt to see the rendered templates here.</p>
                @endif
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Result</h3>
                @if($result)
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <div class="text-gray-500 dark:text-gray-400">Provider</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $result->provider }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500 dark:text-gray-400">Model</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $result->model }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500 dark:text-gray-400">Status</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $result->status->value }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500 dark:text-gray-400">Tokens</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $result->usage?->totalTokens ?? 0 }}</div>
                            </div>
                        </div>

                        @if($aiRequest)
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/40 dark:bg-amber-900/20">
                                <div class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">AI Request</div>
                                <div class="mt-2 flex flex-wrap items-center gap-3">
                                    <div class="font-mono text-sm text-amber-900 dark:text-amber-100">{{ $aiRequest->request_uuid }}</div>
                                    <a href="{{ route('admin.ai-requests.show', $aiRequest) }}" class="text-sm font-medium text-amber-700 hover:underline dark:text-amber-300">
                                        View request
                                    </a>
                                </div>
                            </div>
                        @endif

                        <div>
                            <div class="text-gray-500 dark:text-gray-400">Response</div>
                            <pre class="mt-2 overflow-x-auto rounded-xl bg-gray-50 p-4 text-sm text-gray-800 dark:bg-gray-950 dark:text-gray-200">{{ is_array($result->response) ? json_encode($result->response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : (string) $result->response }}</pre>
                        </div>
                    </div>
                @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">No result yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
