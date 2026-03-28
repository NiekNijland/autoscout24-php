<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Testing;

use NiekNijland\AutoScout24\Data\Listing;
use NiekNijland\AutoScout24\Data\Price;
use NiekNijland\AutoScout24\Data\SuperDeal;

class ListingFactory
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function make(array $overrides = []): Listing
    {
        $id = $overrides['id'] ?? 'b26efa31-229e-4c7b-bdc9-cfb74a3e7467';

        $defaults = [
            'id' => $id,
            'url' => "/aanbod/test-listing-{$id}",
            'images' => ['https://prod.pictures.autoscout24.net/listing-images/test.jpg'],
            'price' => new Price('€ 9.999', 9999),
            'availableNow' => true,
            'superDeal' => new SuperDeal,
            'vehicle' => VehicleFactory::make(),
            'location' => LocationFactory::make(),
            'seller' => SellerFactory::make(),
            'vehicleDetails' => [],
        ];

        $data = array_merge($defaults, $overrides);

        return new Listing(
            id: (string) $data['id'],
            url: (string) $data['url'],
            images: $data['images'],
            price: $data['price'],
            availableNow: (bool) $data['availableNow'],
            superDeal: $data['superDeal'],
            vehicle: $data['vehicle'],
            location: $data['location'],
            seller: $data['seller'],
            vehicleDetails: $data['vehicleDetails'],
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return Listing[]
     */
    public static function makeMany(int $count, array $overrides = []): array
    {
        $listings = [];
        for ($i = 0; $i < $count; $i++) {
            $id = sprintf('%08d-%04d-%04d-%04d-%012d', $i, 0, 0, 0, $i);
            $listings[] = self::make(array_merge($overrides, ['id' => $id]));
        }

        return $listings;
    }
}
