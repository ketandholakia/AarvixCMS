<?php

namespace App\AI\Services;

use App\AI\Exceptions\AiPromptException;
use App\Models\AiPrompt;
use App\Models\AiPromptVersion;
use Illuminate\Support\Str;

class PromptService
{
    /**
     * Render a prompt template with strict placeholder validation.
     *
     * @param array<string, scalar|null> $variables
     */
    public function renderTemplate(string $template, array $variables): string
    {
        $placeholders = $this->placeholders($template);
        $providedKeys = array_keys($variables);

        sort($placeholders);
        sort($providedKeys);

        if ($placeholders !== $providedKeys) {
            $missing = array_values(array_diff($placeholders, $providedKeys));
            $unknown = array_values(array_diff($providedKeys, $placeholders));

            $messageParts = [];

            if ($missing !== []) {
                $messageParts[] = 'missing: ' . implode(', ', $missing);
            }

            if ($unknown !== []) {
                $messageParts[] = 'unknown: ' . implode(', ', $unknown);
            }

            throw new AiPromptException('Prompt variables do not match template placeholders (' . implode('; ', $messageParts) . ').');
        }

        $rendered = preg_replace_callback('/{{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*}}/', function (array $matches) use ($variables) {
            $key = $matches[1];
            $value = $variables[$key];

            if (is_array($value) || is_object($value)) {
                throw new AiPromptException("Prompt variable [{$key}] must be scalar or null.");
            }

            return (string) ($value ?? '');
        }, $template);

        if (! is_string($rendered)) {
            throw new AiPromptException('Prompt rendering failed.');
        }

        if (Str::contains($rendered, ['{{', '}}'])) {
            throw new AiPromptException('Prompt contains unresolved placeholders.');
        }

        return $rendered;
    }

    /**
     * @param array<string, scalar|null> $variables
     */
    public function renderVersion(AiPromptVersion $version, array $variables): array
    {
        $system = $this->renderTemplate(
            $version->system_template,
            $this->filterVariables($version->system_template, $variables)
        );
        $user = $version->user_template !== null
            ? $this->renderTemplate($version->user_template, $this->filterVariables($version->user_template, $variables))
            : null;

        return [
            'system' => $system,
            'user' => $user,
            'output_schema' => $version->output_schema,
            'version_number' => $version->version_number,
        ];
    }

    /**
     * @param array<string, scalar|null> $variables
     */
    public function renderPrompt(AiPrompt $prompt, array $variables): array
    {
        $version = $prompt->activeVersion;

        if (! $version instanceof AiPromptVersion) {
            throw new AiPromptException("Prompt [{$prompt->prompt_key}] does not have an active version.");
        }

        return $this->renderVersion($version, $variables);
    }

    /**
     * @param array<string, scalar|null> $variables
     * @return array<string, scalar|null>
     */
    public function filterVariables(string $template, array $variables): array
    {
        $placeholderKeys = $this->placeholders($template);

        return array_intersect_key($variables, array_flip($placeholderKeys));
    }

    /**
     * @return array<int, string>
     */
    public function placeholders(string $template): array
    {
        preg_match_all('/{{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*}}/', $template, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }
}
