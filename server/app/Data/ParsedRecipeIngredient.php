<?php

namespace App\Data;

readonly class ParsedRecipeIngredient
{
    public function __construct(
        public string $name,
        public ?float $quantity,
        public ?string $quantityDisplay,
        public string $unitName,
        public string $categoryName,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'quantity' => $this->quantity,
            'quantityDisplay' => $this->quantityDisplay,
            'unitName' => $this->unitName,
            'categoryName' => $this->categoryName,
        ];
    }

    public static function fromArray(array $data): self
    {
        $quantity = $data['quantity'] ?? null;
        $quantityDisplay = $data['quantityDisplay'] ?? null;

        return new self(
            name: (string) ($data['name'] ?? ''),
            quantity: $quantity === null || $quantity === '' ? null : (float) $quantity,
            quantityDisplay: $quantityDisplay === null || $quantityDisplay === '' ? null : (string) $quantityDisplay,
            unitName: (string) ($data['unitName'] ?? ''),
            categoryName: (string) ($data['categoryName'] ?? ''),
        );
    }
}
