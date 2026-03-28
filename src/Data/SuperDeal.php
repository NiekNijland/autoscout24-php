<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

readonly class SuperDeal
{
    public function __construct(
        public ?string $oldPriceFormatted = null,
        public bool $isEligible = false,
    ) {}

    /**
     * @param  array{oldPriceFormatted?: string|null, isEligible?: bool}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            oldPriceFormatted: $data['oldPriceFormatted'] ?? null,
            isEligible: (bool) ($data['isEligible'] ?? false),
        );
    }

    /**
     * @return array{oldPriceFormatted: string|null, isEligible: bool}
     */
    public function toArray(): array
    {
        return [
            'oldPriceFormatted' => $this->oldPriceFormatted,
            'isEligible' => $this->isEligible,
        ];
    }
}
