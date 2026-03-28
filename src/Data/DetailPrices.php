<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

readonly class DetailPrices
{
    public function __construct(
        public bool $isFinalPrice,
        public ?DetailPriceInfo $public = null,
        public ?DetailPriceInfo $dealer = null,
        public ?DetailPriceInfo $suggestedRetail = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            isFinalPrice: (bool) ($data['isFinalPrice'] ?? false),
            public: isset($data['public']) ? DetailPriceInfo::fromArray($data['public']) : null,
            dealer: isset($data['dealer']) ? DetailPriceInfo::fromArray($data['dealer']) : null,
            suggestedRetail: isset($data['suggestedRetail']) ? DetailPriceInfo::fromArray($data['suggestedRetail']) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'isFinalPrice' => $this->isFinalPrice,
            'public' => $this->public?->toArray(),
            'dealer' => $this->dealer?->toArray(),
            'suggestedRetail' => $this->suggestedRetail?->toArray(),
        ];
    }
}
