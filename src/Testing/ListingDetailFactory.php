<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Testing;

use NiekNijland\AutoScout24\Data\DetailPriceInfo;
use NiekNijland\AutoScout24\Data\DetailPrices;
use NiekNijland\AutoScout24\Data\ListingDetail;
use NiekNijland\AutoScout24\Data\Price;

class ListingDetailFactory
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function make(array $overrides = []): ListingDetail
    {
        $id = $overrides['id'] ?? 'b26efa31-229e-4c7b-bdc9-cfb74a3e7467';

        $defaults = [
            'id' => $id,
            'description' => 'A <strong>great</strong> vehicle<br />Well maintained.',
            'images' => ['https://prod.pictures.autoscout24.net/listing-images/test.jpg/1280x960.webp'],
            'prices' => new DetailPrices(
                isFinalPrice: false,
                public: new DetailPriceInfo(price: '€ 9.999', priceRaw: 9999),
            ),
            'price' => new Price('€ 9.999', 9999),
            'vehicle' => DetailVehicleFactory::make(),
            'seller' => SellerFactory::make(),
            'location' => LocationFactory::make(),
            'url' => "/aanbod/test-detail-{$id}",
        ];

        $data = array_merge($defaults, $overrides);

        return new ListingDetail(
            id: (string) $data['id'],
            description: $data['description'],
            images: $data['images'],
            prices: $data['prices'],
            price: $data['price'],
            vehicle: $data['vehicle'],
            seller: $data['seller'],
            location: $data['location'],
            url: $data['url'],
        );
    }
}
