<?php

namespace App\AI\Exceptions;

class AiImageCapabilityException extends AiCapabilityException
{
    public function __construct(string $message, protected array $errors = [])
    {
        parent::__construct($message);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
