<?php

namespace App\Http\Requests\Admin;

use App\Models\Entry;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class AiWriterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user || ! $user->is_active) {
            return false;
        }

        $context = (string) $this->input('context');
        $subject = $this->resolveSubject($context, $this->input('record_id'), $this->input('content_type_slug'));

        if ($subject instanceof Post || $subject instanceof Page) {
            return $user->can('update', $subject);
        }

        if ($subject instanceof Entry) {
            $slug = $subject->contentType?->slug;

            return is_string($slug) && $slug !== '' && $user->hasPermission("edit_{$slug}");
        }

        if ($context === 'post') {
            return $user->hasPermission('create_posts');
        }

        if ($context === 'page') {
            return $user->hasPermission('create_pages');
        }

        if ($context === 'entry') {
            $slug = $this->input('content_type_slug');

            return is_string($slug) && $slug !== '' && $user->hasPermission("create_{$slug}");
        }

        return false;
    }

    public function rules(): array
    {
        return [
            'context' => ['required', Rule::in(['post', 'page', 'entry'])],
            'operation' => ['required', Rule::in(['rewrite', 'shorten', 'expand', 'summarize', 'grammar', 'seo'])],
            'scope' => ['nullable', Rule::in(['document', 'selection'])],
            'document' => ['required', 'string'],
            'selection' => ['nullable', 'string', 'max:20000'],
            'record_id' => ['nullable', 'integer'],
            'content_type_slug' => ['nullable', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:255'],
            'tone' => ['nullable', 'string', 'max:50'],
        ];
    }

    protected function resolveSubject(string $context, mixed $recordId, mixed $contentTypeSlug): Model|Post|Page|Entry|null
    {
        return match ($context) {
            'post' => empty($recordId) ? null : Post::find($recordId),
            'page' => empty($recordId) ? null : Page::find($recordId),
            'entry' => $this->resolveEntry($recordId, $contentTypeSlug),
            default => null,
        };
    }

    protected function resolveEntry(mixed $recordId, mixed $contentTypeSlug): ?Entry
    {
        if (empty($recordId)) {
            return null;
        }

        $entry = Entry::with('contentType')->find($recordId);

        if (! $entry) {
            return null;
        }

        if (is_string($contentTypeSlug) && $contentTypeSlug !== '' && $entry->contentType?->slug !== $contentTypeSlug) {
            return null;
        }

        return $entry;
    }
}
