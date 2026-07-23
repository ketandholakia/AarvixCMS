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
            ),
            new AiToolDefinition(
                key: 'seo.propose',
                version: 1,
                name: 'Propose SEO Metadata',
                description: 'Draft SEO metadata for review without applying changes automatically.',
                category: 'writer',
                handler: 'proposeSeoMetadata',
                requiredPermission: 'edit_posts',
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
            ),
        ];
    }
}
