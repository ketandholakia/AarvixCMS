<?php

namespace App\AI\Services;

class TextEmbeddingService
{
    /**
     * @return array<int, float>
     */
    public function vectorize(string $text, int $dimensions = 12): array
    {
        $dimensions = max(4, $dimensions);
        $vector = array_fill(0, $dimensions, 0.0);
        $tokens = preg_split('/[^a-z0-9]+/i', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($tokens as $token) {
            $index = hexdec(substr(hash('sha1', $token), 0, 8)) % $dimensions;
            $weight = 1.0 + (strlen($token) / 10);
            $vector[$index] += $weight;
        }

        $magnitude = sqrt(array_reduce($vector, static fn (float $carry, float $value): float => $carry + ($value ** 2), 0.0));

        if ($magnitude <= 0.0) {
            return $vector;
        }

        return array_map(static fn (float $value): float => round($value / $magnitude, 8), $vector);
    }
}
