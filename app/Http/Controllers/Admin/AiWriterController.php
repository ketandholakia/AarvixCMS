<?php

namespace App\Http\Controllers\Admin;

use App\AI\DTOs\AiRequestData;
use App\AI\Services\AiManager;
use App\AI\Exceptions\AiCapabilityException;
use App\AI\Exceptions\AiProviderException;
use App\AI\Exceptions\AiRateLimitException;
use App\AI\Support\WriterDocument;
use App\AI\Support\WriterPreview;
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

        $writerDocument = WriterDocument::fromEditorJs(
            $data['document'],
            $data['selection'] ?? null,
            $data['scope'] ?? 'document'
        );

        try {
            $result = $aiManager->generate(new AiRequestData(
                input: [
                    'operation' => $data['operation'],
                    'context' => $data['context'],
                    'document' => $writerDocument,
                    'content' => $writerDocument['plain_text'],
                    'selection' => $writerDocument['selection'],
                    'title' => $data['title'] ?? ($subject?->title ?? null),
                    'tone' => $data['tone'] ?? null,
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
        } catch (AiRateLimitException $e) {
            return $this->writerErrorResponse(
                'AI rate limit reached. Please try again shortly.',
                $e,
                429,
                $writerDocument
            );
        } catch (AiCapabilityException|AiProviderException $e) {
            return $this->writerErrorResponse(
                'AI writer could not generate a preview. Your content was not changed.',
                $e,
                503,
                $writerDocument
            );
        } catch (\Throwable $e) {
            report($e);

            return $this->writerErrorResponse(
                'AI writer could not generate a preview. Your content was not changed.',
                $e,
                500,
                $writerDocument
            );
        }

        $preview = WriterPreview::fromResponse($result->response, $data['operation'], $writerDocument, $data['scope'] ?? 'document');

        return response()->json([
            'status' => $result->status->value,
            'request_id' => $result->requestId,
            'provider' => $result->provider,
            'model' => $result->model,
            'suggestion' => $preview['plain_text'],
            'preview' => $preview,
            'response' => $result->response,
        ]);
    }

    protected function writerErrorResponse(string $message, \Throwable $error, int $statusCode, array $writerDocument): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 'failed',
            'message' => $message,
            'response' => null,
            'suggestion' => null,
            'preview' => [
                'mode' => $writerDocument['scope'] === 'selection' ? 'insert' : 'replace',
                'actions' => ['replace', 'cancel'],
                'summary' => 'Preview unavailable',
                'plain_text' => $writerDocument['plain_text'] ?? '',
                'blocks' => WriterPreview::blocksFromText($writerDocument['plain_text'] ?? ''),
                'seo' => null,
            ],
            'error' => [
                'class' => class_basename($error),
            ],
        ], $statusCode);
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
}
