<?php

namespace App\AI\Enums;

enum AiCapability: string
{
    case Generate = 'generate';
    case Stream = 'stream';
    case Chat = 'chat';
    case Embedding = 'embedding';
    case Image = 'image';
    case Vision = 'vision';
    case Json = 'json';

    public static function fromMethod(string $method): self
    {
        return match ($method) {
            'generate' => self::Generate,
            'stream' => self::Stream,
            'chat' => self::Chat,
            'embedding' => self::Embedding,
            'image' => self::Image,
            'vision' => self::Vision,
            'json' => self::Json,
            default => throw new \InvalidArgumentException("Unsupported AI method [{$method}]."),
        };
    }
}
