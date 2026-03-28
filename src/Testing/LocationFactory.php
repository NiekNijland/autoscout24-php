<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Testing;

use NiekNijland\AutoScout24\Data\Location;

class LocationFactory
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function make(array $overrides = []): Location
    {
        $defaults = [
            'countryCode' => 'NL',
            'zip' => '2411 NE',
            'city' => 'BODEGRAVEN',
            'street' => 'Europaweg 1D',
            'latitude' => null,
            'longitude' => null,
        ];

        $data = array_merge($defaults, $overrides);

        return new Location(
            countryCode: (string) $data['countryCode'],
            zip: $data['zip'],
            city: $data['city'],
            street: $data['street'],
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
        );
    }
}
