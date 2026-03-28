<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Testing;

use NiekNijland\AutoScout24\Data\Vehicle;

class VehicleFactory
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function make(array $overrides = []): Vehicle
    {
        $defaults = [
            'articleType' => 'Car',
            'type' => 'Car',
            'make' => 'Volkswagen',
            'model' => 'Golf',
            'modelId' => 12345,
            'offerType' => 'U',
            'mileageInKm' => '50.000 km',
            'mileageInKmRaw' => 50000,
            'modelGroup' => null,
            'variant' => null,
            'subtitle' => null,
        ];

        $data = array_merge($defaults, $overrides);

        return new Vehicle(
            articleType: (string) $data['articleType'],
            type: (string) $data['type'],
            make: (string) $data['make'],
            model: (string) $data['model'],
            modelId: (int) $data['modelId'],
            offerType: (string) $data['offerType'],
            mileageInKm: (string) $data['mileageInKm'],
            mileageInKmRaw: $data['mileageInKmRaw'],
            modelGroup: $data['modelGroup'],
            variant: $data['variant'],
            subtitle: $data['subtitle'],
        );
    }
}
