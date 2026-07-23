<?php

namespace App\Http\Controllers\Admin;

use App\AI\DTOs\AiRequestData;
use App\AI\Services\AiManager;
use App\AI\Support\WriterDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AiWriterRequest;
use App\Models\Entry;
use Illuminate\Database\Eloquent\Model;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Support\Str;

class AiWriterController extends Controller
{
    public function generate(AiWriterRequest $request, AiManager $aiManager)
    {
        $data = $request->validated();
        $subject = $this->resolveSubject($data);
        $this->authorizeSubject($data['context'], $subject);

        $writerDocument = WriterDocument::fromEditorJs(
            $data['document'],
            $data['selection'] ?? null,
            $data['scope'] ?? 'document'
        );

        $result = $aiManager->generate(new AiRequestData(
            input: [
                'operation' => $data['operation'],
                'context' => $data['context'],
                'document' => $writerDocument,
                'content' => $writerDocument['plain_text'],
                'selection' => $writerDocument['selection'],
                'tone' => $data['tone'] ?? null,
                'title' => $subject?->title ?? null,
            ],
            options: [
                'content_length' => Str::length($writerDocument['plain_text']),
                'context_model' => $subject ? $subject::class : null,
            ],
            provider: config('ai.default_provider', 'fake'),
            model: data_get(config('ai.models.writer'), 'model', 'fake-writer'),
            promptKey: 'writer.' . $data['operation'],
            feature: 'writer',
        ));

        return response()->json([
            'status' => $result->status->value,
            'request_id' => $result->requestId,
            'provider' => $result->provider,
            'model' => $result->model,
            'suggestion' => $this->suggestionFromResult($result->response, $data['operation'], $writerDocument['plain_text']),
            'response' => $result->response,
        ]);
    }

    protected function resolveSubject(array $data): Model|Post|Page|Entry|null
    {
        return match ($data['context']) {
            'post' => empty($data['record_id']) ? null : Post::findOrFail($data['record_id']),
            'page' => empty($data['record_id']) ? null : Page::findOrFail($data['record_id']),
            'entry' => $this->resolveEntry($data),
        };
    }

    protected function resolveEntry(array $data): ?Entry
    {
        if (empty($data['record_id'])) {
            return null;
        }

        $entry = Entry::with('contentType')->findOrFail($data['record_id']);

        if (! empty($data['content_type_slug']) && $entry->contentType?->slug !== $data['content_type_slug']) {
            abort(404);
        }

        return $entry;
    }

    protected function authorizeSubject(string $context, Model|Post|Page|Entry|null $subject): void
    {
        if ($subject instanceof Post || $subject instanceof Page) {
            $this->authorize('update', $subject);
            return;
        }

        if ($subject instanceof Entry) {
            $slug = $subject->contentType?->slug;
            if (! $slug || ! auth()->user()?->hasPermission("edit_{$slug}")) {
                abort(403, 'You do not have the required permissions.');
            }

            return;
        }

        if ($context === 'post' && ! auth()->user()?->hasPermission('create_posts')) {
            abort(403, 'You do not have the required permissions.');
        }

        if ($context === 'page' && ! auth()->user()?->hasPermission('create_pages')) {
            abort(403, 'You do not have the required permissions.');
        }

        if ($context === 'entry') {
            $slug = request('content_type_slug');
            if (! is_string($slug) || $slug === '' || ! auth()->user()?->hasPermission("create_{$slug}")) {
                abort(403, 'You do not have the required permissions.');
            }
        }
    }


    protected function suggestionFromResult(mixed $response, string $operation, string $content): string
    {
        if (is_string($response) && trim($response) !== '') {
            return trim($response);
        }

        if (is_array($response)) {
            foreach (['suggestion', 'content', 'text', 'output'] as $key) {
                if (isset($response[$key]) && is_string($response[$key]) && trim($response[$key]) !== '') {
                    return trim($response[$key]);
                }
            }
        }

        return match ($operation) {
            'shorten' => Str::limit($content, 240),
            'expand' => $content . "\n\nExpanded version coming from the selected provider.",
            'summarize' => 'Summary: ' . Str::limit($content, 180),
            'grammar' => $content,
            default => 'Suggested rewrite: ' . Str::limit($content, 240),
        };
    }
}
