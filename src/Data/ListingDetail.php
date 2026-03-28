<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

readonly class ListingDetail
{
    /**
     * @param  string[]  $images
     * @param  string|null  $url  The listing URL. Unlike Listing::$url (always present in search results),
     *                            this is nullable because the detail endpoint may not include it.
     */
    public function __construct(
        public string $id,
        public ?string $description,
        public array $images,
        public DetailPrices $prices,
        public Price $price,
        public DetailVehicle $vehicle,
        public Seller $seller,
        public Location $location,
        public ?string $url = null,
    ) {}

    public function descriptionText(): ?string
    {
        if ($this->description === null) {
            return null;
        }

        return trim(strip_tags($this->description));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            description: $data['description'] ?? null,
            images: $data['images'] ?? [],
            prices: DetailPrices::fromArray($data['prices'] ?? []),
            price: Price::fromArray($data['price'] ?? []),
            vehicle: DetailVehicle::fromArray($data['vehicle'] ?? []),
            seller: Seller::fromArray($data['seller'] ?? []),
            location: Location::fromArray($data['location'] ?? []),
            url: $data['url'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'images' => $this->images,
            'prices' => $this->prices->toArray(),
            'price' => $this->price->toArray(),
            'vehicle' => $this->vehicle->toArray(),
            'seller' => $this->seller->toArray(),
            'location' => $this->location->toArray(),
            'url' => $this->url,
        ];
    }
}
