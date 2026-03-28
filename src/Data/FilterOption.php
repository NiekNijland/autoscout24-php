<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

readonly class FilterOption
{
    public function __construct(
        public int|string $value,
        public string $label,
    ) {}

    /**
     * @param  array{value: int|string, label: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            value: $data['value'],
            label: (string) $data['label'],
        );
    }

    /**
     * @return array{value: int|string, label: string}
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label,
        ];
    }
}
