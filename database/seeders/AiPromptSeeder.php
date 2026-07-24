<?php

namespace Database\Seeders;

use App\Models\AiPrompt;
use App\Models\AiPromptVersion;
use Illuminate\Database\Seeder;

class AiPromptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->writerPrompts() as $definition) {
            $prompt = AiPrompt::updateOrCreate(
                ['prompt_key' => $definition['prompt_key']],
                [
                    'category' => $definition['category'],
                    'title' => $definition['title'],
                    'description' => $definition['description'],
                    'active_version_number' => 1,
                    'output_schema' => $definition['output_schema'],
                    'is_enabled' => true,
                ]
            );

            AiPromptVersion::updateOrCreate(
                [
                    'ai_prompt_id' => $prompt->id,
                    'version_number' => 1,
                ],
                [
                    'system_template' => $definition['system_template'],
                    'user_template' => $definition['user_template'],
                    'variables' => $definition['variables'],
                    'output_schema' => $definition['output_schema'],
                    'change_summary' => $definition['change_summary'],
                ]
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function writerPrompts(): array
    {
        return [
            [
                'prompt_key' => 'writer.rewrite',
                'category' => 'writer',
                'title' => 'Rewrite',
                'description' => 'Rewrite content while preserving meaning and links.',
                'system_template' => 'You are AarvixCMS\'s editorial assistant. Rewrite content while preserving the original meaning, facts, and links. Follow this style guide when provided: {{style_guide}}.',
                'user_template' => "Rewrite the following content in a {{tone}} tone:\n\n{{content}}",
                'variables' => [
                    'tone' => 'friendly',
                    'content' => 'Original content to rewrite.',
                    'style_guide' => 'House editorial guidelines.',
                ],
                'output_schema' => [],
                'change_summary' => 'Initial rewrite prompt seed.',
            ],
            [
                'prompt_key' => 'writer.shorten',
                'category' => 'writer',
                'title' => 'Shorten',
                'description' => 'Condense content without removing the important points.',
                'system_template' => 'You are AarvixCMS\'s editorial assistant. Shorten the supplied content without losing critical meaning. Follow this style guide when provided: {{style_guide}}.',
                'user_template' => "Shorten the following content:\n\n{{content}}",
                'variables' => [
                    'content' => 'Original content to shorten.',
                    'style_guide' => 'House editorial guidelines.',
                ],
                'output_schema' => [],
                'change_summary' => 'Initial shorten prompt seed.',
            ],
            [
                'prompt_key' => 'writer.expand',
                'category' => 'writer',
                'title' => 'Expand',
                'description' => 'Expand content with useful detail while staying on topic.',
                'system_template' => 'You are AarvixCMS\'s editorial assistant. Expand the supplied content while staying on topic and keeping the tone consistent. Follow this style guide when provided: {{style_guide}}.',
                'user_template' => "Expand the following content:\n\n{{content}}",
                'variables' => [
                    'content' => 'Original content to expand.',
                    'style_guide' => 'House editorial guidelines.',
                ],
                'output_schema' => [],
                'change_summary' => 'Initial expand prompt seed.',
            ],
            [
                'prompt_key' => 'writer.summarize',
                'category' => 'writer',
                'title' => 'Summarize',
                'description' => 'Summarize content with a concise, faithful summary.',
                'system_template' => 'You are AarvixCMS\'s editorial assistant. Summarize the supplied content clearly and faithfully. Follow this style guide when provided: {{style_guide}}.',
                'user_template' => "Summarize the following content:\n\n{{content}}",
                'variables' => [
                    'content' => 'Original content to summarize.',
                    'style_guide' => 'House editorial guidelines.',
                ],
                'output_schema' => [],
                'change_summary' => 'Initial summarize prompt seed.',
            ],
            [
                'prompt_key' => 'writer.grammar',
                'category' => 'writer',
                'title' => 'Grammar',
                'description' => 'Fix grammar and spelling without changing meaning.',
                'system_template' => 'You are AarvixCMS\'s editorial assistant. Correct grammar, spelling, and punctuation without changing meaning. Follow this style guide when provided: {{style_guide}}.',
                'user_template' => "Correct the grammar in the following content:\n\n{{content}}",
                'variables' => [
                    'content' => 'Original content to proofread.',
                    'style_guide' => 'House editorial guidelines.',
                ],
                'output_schema' => [],
                'change_summary' => 'Initial grammar prompt seed.',
            ],
            [
                'prompt_key' => 'writer.seo',
                'category' => 'writer',
                'title' => 'SEO Metadata',
                'description' => 'Generate SEO metadata from article content.',
                'system_template' => 'You are AarvixCMS\'s SEO assistant. Generate clean metadata from the supplied article content only. Follow this style guide when provided: {{style_guide}}.',
                'user_template' => "Create SEO metadata for this article.\n\nTitle: {{title}}\n\nContent: {{content}}",
                'variables' => [
                    'title' => 'Example article title',
                    'content' => 'Original article content.',
                    'style_guide' => 'House editorial guidelines.',
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'meta_title' => ['type' => 'string'],
                        'meta_description' => ['type' => 'string'],
                        'slug' => ['type' => 'string'],
                        'keywords' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                        'og_title' => ['type' => 'string'],
                        'og_description' => ['type' => 'string'],
                        'twitter_title' => ['type' => 'string'],
                        'twitter_description' => ['type' => 'string'],
                        'warnings' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                    ],
                ],
                'change_summary' => 'Initial SEO prompt seed.',
            ],
        ];
    }
}
