<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

readonly class DetailPriceInfo
{
    public function __construct(
        public string $price,
        public int $priceRaw,
        public bool $taxDeductible = false,
        public bool $negotiable = false,
        public bool $onRequestOnly = false,
        public ?string $netPrice = null,
        public ?int $netPriceRaw = null,
        public ?string $vatRate = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            price: (string) ($data['price'] ?? ''),
            priceRaw: (int) ($data['priceRaw'] ?? 0),
            taxDeductible: (bool) ($data['taxDeductible'] ?? false),
            negotiable: (bool) ($data['negotiable'] ?? false),
            onRequestOnly: (bool) ($data['onRequestOnly'] ?? false),
            netPrice: $data['netPrice'] ?? null,
            netPriceRaw: isset($data['netPriceRaw']) ? (int) $data['netPriceRaw'] : null,
            vatRate: $data['vatRate'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'price' => $this->price,
            'priceRaw' => $this->priceRaw,
            'taxDeductible' => $this->taxDeductible,
            'negotiable' => $this->negotiable,
            'onRequestOnly' => $this->onRequestOnly,
            'netPrice' => $this->netPrice,
            'netPriceRaw' => $this->netPriceRaw,
            'vatRate' => $this->vatRate,
        ];
    }
}
