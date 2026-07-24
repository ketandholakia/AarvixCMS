<?php

namespace Database\Seeders;

use App\AI\DTOs\AiToolDefinition;
use App\AI\Services\AiToolRegistryService;
use Illuminate\Database\Seeder;

class AiToolSeeder extends Seeder
{
    public function run(AiToolRegistryService $registry): void
    {
        $registry->syncDefinitions($this->definitions());
    }

    /**
     * @return array<int, AiToolDefinition>
     */
    protected function definitions(): array
    {
        return [
            new AiToolDefinition(
                key: 'content.search',
                version: 1,
                name: 'Search Content',
                description: 'Search authorized CMS content and return matching summaries.',
                category: 'content',
                handler: 'searchContent',
                requiredPermission: 'view_posts',
                confirmationPolicy: 'never',
                riskClassification: 'read',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string'],
                        'limit' => ['type' => 'integer'],
                    ],
                ],
                outputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'items' => ['type' => 'array'],
                    ],
                ],
                timeoutSeconds: 20,
                rateLimitPerMinute: 60,
                auditRedactionPolicy: 'minimal',
            ),
            new AiToolDefinition(
                key: 'content.summary',
                version: 1,
                name: 'Summarize Content',
                description: 'Produce a concise summary for authorized content.',
                category: 'content',
                handler: 'summarizeContent',
                requiredPermission: 'view_posts',
                confirmationPolicy: 'never',
                riskClassification: 'read',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'source_type' => ['type' => 'string'],
                        'source_id' => ['type' => 'integer'],
                    ],
                ],
                outputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'summary' => ['type' => 'string'],
                    ],
                ],
                timeoutSeconds: 30,
                rateLimitPerMinute: 40,
                auditRedactionPolicy: 'minimal',
            ),
            new AiToolDefinition(
                key: 'media.search',
                version: 1,
                name: 'Search Media',
                description: 'Search the media library by filename, caption, or alt text.',
                category: 'media',
                handler: 'searchMedia',
                requiredPermission: 'manage_media',
                confirmationPolicy: 'never',
                riskClassification: 'read',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string'],
                        'limit' => ['type' => 'integer'],
                        'mime_type' => ['type' => 'string'],
                        'images_only' => ['type' => 'boolean'],
                    ],
                ],
                outputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'count' => ['type' => 'integer'],
                        'items' => ['type' => 'array'],
                    ],
                ],
                timeoutSeconds: 15,
                rateLimitPerMinute: 60,
                auditRedactionPolicy: 'minimal',
            ),
            new AiToolDefinition(
                key: 'content.draft',
                version: 1,
                name: 'Create Draft Article',
                description: 'Create a draft post for editorial review.',
                category: 'content',
                handler: 'createDraftArticle',
                requiredPermission: 'create_posts',
                confirmationPolicy: 'review',
                riskClassification: 'write',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'slug' => ['type' => 'string'],
                        'excerpt' => ['type' => 'string'],
                        'body' => ['type' => 'string'],
                        'category_id' => ['type' => 'integer'],
                        'meta_title' => ['type' => 'string'],
                        'meta_description' => ['type' => 'string'],
                    ],
                    'required' => ['title'],
                ],
                outputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'source_type' => ['type' => 'string'],
                        'source_id' => ['type' => 'integer'],
                        'title' => ['type' => 'string'],
                        'slug' => ['type' => 'string'],
                        'status' => ['type' => 'string'],
                        'edit_url' => ['type' => 'string'],
                    ],
                ],
                timeoutSeconds: 60,
                rateLimitPerMinute: 10,
                auditRedactionPolicy: 'partial',
            ),
            new AiToolDefinition(
                key: 'ai.report',
                version: 1,
                name: 'AI Tool Call Report',
                description: 'Summarize or export tool-call activity for administrators.',
                category: 'analytics',
                handler: 'exportToolCalls',
                requiredPermission: 'view_ai_usage',
                confirmationPolicy: 'never',
                riskClassification: 'read',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'tool_key' => ['type' => 'string'],
                        'status' => ['type' => 'string'],
                        'approval_state' => ['type' => 'string'],
                        'started_after' => ['type' => 'string'],
                        'started_before' => ['type' => 'string'],
                        'limit' => ['type' => 'integer'],
                        'format' => ['type' => 'string'],
                    ],
                ],
                outputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'format' => ['type' => 'string'],
                        'summary' => ['type' => 'object'],
                        'rows' => ['type' => 'array'],
                        'csv' => ['type' => 'string'],
                    ],
                ],
                timeoutSeconds: 20,
                rateLimitPerMinute: 30,
                auditRedactionPolicy: 'minimal',
            ),
            new AiToolDefinition(
                key: 'seo.propose',
                version: 1,
                name: 'Propose SEO Metadata',
                description: 'Draft SEO metadata for review without applying changes automatically.',
                category: 'writer',
                handler: 'proposeSeoMetadata',
                requiredPermission: 'use_ai_writer',
                confirmationPolicy: 'review',
                riskClassification: 'write',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'content' => ['type' => 'string'],
                    ],
                ],
                outputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'meta_title' => ['type' => 'string'],
                        'meta_description' => ['type' => 'string'],
                    ],
                ],
                timeoutSeconds: 30,
                rateLimitPerMinute: 20,
                auditRedactionPolicy: 'minimal',
            ),
        ];
    }
}
