<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

readonly class Listing
{
    /**
     * @param  string[]  $images
     * @param  VehicleDetail[]  $vehicleDetails
     */
    public function __construct(
        public string $id,
        public string $url,
        public array $images,
        public Price $price,
        public bool $availableNow,
        public SuperDeal $superDeal,
        public Vehicle $vehicle,
        public Location $location,
        public Seller $seller,
        public array $vehicleDetails = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $vehicleDetails = array_map(
            static fn (array $detail): VehicleDetail => VehicleDetail::fromArray($detail),
            $data['vehicleDetails'] ?? [],
        );

        return new self(
            id: (string) $data['id'],
            url: (string) $data['url'],
            images: $data['images'] ?? [],
            price: Price::fromArray($data['price'] ?? []),
            availableNow: (bool) ($data['availableNow'] ?? false),
            superDeal: SuperDeal::fromArray($data['superDeal'] ?? []),
            vehicle: Vehicle::fromArray($data['vehicle'] ?? []),
            location: Location::fromArray($data['location'] ?? []),
            seller: Seller::fromArray($data['seller'] ?? []),
            vehicleDetails: $vehicleDetails,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'images' => $this->images,
            'price' => $this->price->toArray(),
            'availableNow' => $this->availableNow,
            'superDeal' => $this->superDeal->toArray(),
            'vehicle' => $this->vehicle->toArray(),
            'location' => $this->location->toArray(),
            'seller' => $this->seller->toArray(),
            'vehicleDetails' => array_map(static fn (VehicleDetail $d): array => $d->toArray(), $this->vehicleDetails),
        ];
    }
}
