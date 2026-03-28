<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

readonly class VehicleDetail
{
    public function __construct(
        public string $data,
        public string $iconName,
        public string $ariaLabel,
        public bool $isPlaceholder = false,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            data: (string) ($data['data'] ?? ''),
            iconName: (string) ($data['iconName'] ?? ''),
            ariaLabel: (string) ($data['ariaLabel'] ?? ''),
            isPlaceholder: (bool) ($data['isPlaceholder'] ?? false),
        );
    }

    /**
     * @return array{data: string, iconName: string, ariaLabel: string, isPlaceholder: bool}
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'iconName' => $this->iconName,
            'ariaLabel' => $this->ariaLabel,
            'isPlaceholder' => $this->isPlaceholder,
        ];
    }
}
