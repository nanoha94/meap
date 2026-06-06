<?php

namespace App\Data;

readonly class ParsedRecipeStep
{
    public function __construct(
        public string $instruction,
    ) {}

    public function toArray(): array
    {
        return [
            'instruction' => $this->instruction,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            instruction: (string) ($data['instruction'] ?? ''),
        );
    }
}
