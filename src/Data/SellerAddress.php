<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

readonly class SellerAddress
{
    public function __construct(
        public ?string $zip = null,
        public ?string $city = null,
        public ?string $countryCode = null,
        public ?string $street = null,
    ) {}

    /**
     * @param  array{zip?: string|null, city?: string|null, countryCode?: string|null, street?: string|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            zip: $data['zip'] ?? null,
            city: $data['city'] ?? null,
            countryCode: $data['countryCode'] ?? null,
            street: $data['street'] ?? null,
        );
    }

    /**
     * @return array{zip: string|null, city: string|null, countryCode: string|null, street: string|null}
     */
    public function toArray(): array
    {
        return [
            'zip' => $this->zip,
            'city' => $this->city,
            'countryCode' => $this->countryCode,
            'street' => $this->street,
        ];
    }
}
