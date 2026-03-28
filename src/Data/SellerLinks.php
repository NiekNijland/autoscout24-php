<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

readonly class SellerLinks
{
    public function __construct(
        public ?string $infoPage = null,
        public ?string $imprint = null,
    ) {}

    /**
     * @param  array{infoPage?: string|null, imprint?: string|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            infoPage: $data['infoPage'] ?? null,
            imprint: $data['imprint'] ?? null,
        );
    }

    /**
     * @return array{infoPage: string|null, imprint: string|null}
     */
    public function toArray(): array
    {
        return [
            'infoPage' => $this->infoPage,
            'imprint' => $this->imprint,
        ];
    }
}
