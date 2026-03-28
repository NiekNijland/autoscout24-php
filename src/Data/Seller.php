<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

readonly class Seller
{
    /**
     * @param  Phone[]  $phones
     */
    public function __construct(
        public string $id,
        public string $type,
        public ?string $companyName = null,
        public ?string $contactName = null,
        public array $phones = [],
        public ?SellerLinks $links = null,
        public ?SellerAddress $address = null,
        public ?float $reviewRating = null,
        public ?int $reviewCount = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $phones = array_map(
            static fn (array $phone): Phone => Phone::fromArray($phone),
            $data['phones'] ?? [],
        );

        return new self(
            id: (string) $data['id'],
            type: (string) $data['type'],
            companyName: $data['companyName'] ?? null,
            contactName: $data['contactName'] ?? null,
            phones: $phones,
            links: isset($data['links']) ? SellerLinks::fromArray($data['links']) : null,
            address: isset($data['address']) ? SellerAddress::fromArray($data['address']) : null,
            reviewRating: isset($data['reviewRating']) ? (float) $data['reviewRating'] : null,
            reviewCount: isset($data['reviewCount']) ? (int) $data['reviewCount'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'companyName' => $this->companyName,
            'contactName' => $this->contactName,
            'phones' => array_map(static fn (Phone $p): array => $p->toArray(), $this->phones),
            'links' => $this->links?->toArray(),
            'address' => $this->address?->toArray(),
            'reviewRating' => $this->reviewRating,
            'reviewCount' => $this->reviewCount,
        ];
    }
}
