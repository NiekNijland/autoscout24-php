<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

readonly class Brand
{
    public function __construct(
        public int $value,
        public string $label,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            value: (int) ($data['value'] ?? 0),
            label: (string) ($data['label'] ?? ''),
        );
    }

    /**
     * @return array{value: int, label: string}
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label,
        ];
    }
}
