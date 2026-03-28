<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Testing;

use NiekNijland\AutoScout24\Data\DetailVehicle;
use NiekNijland\AutoScout24\Data\VehicleFuelInfo;

class DetailVehicleFactory
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function make(array $overrides = []): DetailVehicle
    {
        $defaults = [
            'makeId' => 74,
            'modelOrModelLineId' => 12345,
            'make' => 'Volkswagen',
            'model' => 'Golf',
            'modelId' => 12345,
            'type' => 'Car',
            'mileageInKmRaw' => 50000,
            'mileageInKm' => '50.000 km',
            'firstRegistrationDateRaw' => '2020-06-01',
            'firstRegistrationDate' => '06/2020',
            'bodyType' => 'Hatchback',
            'bodyColor' => 'Zwart',
            'rawPowerInKw' => 110,
            'rawPowerInHp' => 150,
            'powerInKw' => '110 kW',
            'powerInHp' => '150 PK',
        ];

        $data = array_merge($defaults, $overrides);

        return new DetailVehicle(
            makeId: (int) $data['makeId'],
            modelOrModelLineId: (int) $data['modelOrModelLineId'],
            make: (string) $data['make'],
            model: (string) $data['model'],
            modelId: (int) $data['modelId'],
            type: (string) $data['type'],
            mileageInKmRaw: (int) $data['mileageInKmRaw'],
            mileageInKm: (string) $data['mileageInKm'],
            firstRegistrationDateRaw: $data['firstRegistrationDateRaw'] ?? null,
            firstRegistrationDate: $data['firstRegistrationDate'] ?? null,
            bodyType: $data['bodyType'] ?? null,
            bodyColor: $data['bodyColor'] ?? null,
            rawPowerInKw: isset($data['rawPowerInKw']) ? (int) $data['rawPowerInKw'] : null,
            rawPowerInHp: isset($data['rawPowerInHp']) ? (int) $data['rawPowerInHp'] : null,
            powerInKw: $data['powerInKw'] ?? null,
            powerInHp: $data['powerInHp'] ?? null,
            fuelCategory: $data['fuelCategory'] ?? new VehicleFuelInfo('B', 'Benzine'),
            primaryFuel: $data['primaryFuel'] ?? new VehicleFuelInfo('B', 'Benzine'),
        );
    }
}
