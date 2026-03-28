<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

readonly class DetailVehicle
{
    public function __construct(
        public int $makeId,
        public int $modelOrModelLineId,
        public string $make,
        public string $model,
        public int $modelId,
        public string $type,
        public int $mileageInKmRaw,
        public string $mileageInKm,
        public ?string $firstRegistrationDateRaw = null,
        public ?string $firstRegistrationDate = null,
        public ?string $bodyType = null,
        public ?string $bodyColor = null,
        public ?string $bodyColorOriginal = null,
        public ?string $paintType = null,
        public ?int $numberOfSeats = null,
        public ?int $numberOfDoors = null,
        public ?string $powerInKw = null,
        public ?string $powerInHp = null,
        public ?int $rawPowerInKw = null,
        public ?int $rawPowerInHp = null,
        public ?string $transmissionType = null,
        public ?int $gears = null,
        public ?int $cylinders = null,
        public ?string $driveTrain = null,
        public ?int $rawDisplacementInCCM = null,
        public ?string $weight = null,
        public ?string $licensePlate = null,
        public ?string $upholstery = null,
        public ?string $upholsteryColor = null,
        public ?string $modelGroup = null,
        public ?string $variant = null,
        public ?string $offerType = null,
        public ?VehicleFuelInfo $fuelCategory = null,
        public ?VehicleFuelInfo $primaryFuel = null,
        public ?string $emissionClass = null,
        public ?string $co2EmissionCombinedFormatted = null,
        public bool $hasParticleFilter = false,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            makeId: (int) ($data['makeId'] ?? 0),
            modelOrModelLineId: (int) ($data['modelOrModelLineId'] ?? 0),
            make: (string) ($data['make'] ?? ''),
            model: (string) ($data['model'] ?? ''),
            modelId: (int) ($data['modelId'] ?? 0),
            type: (string) ($data['type'] ?? ''),
            mileageInKmRaw: (int) ($data['mileageInKmRaw'] ?? 0),
            mileageInKm: (string) ($data['mileageInKm'] ?? ''),
            firstRegistrationDateRaw: $data['firstRegistrationDateRaw'] ?? null,
            firstRegistrationDate: $data['firstRegistrationDate'] ?? null,
            bodyType: $data['bodyType'] ?? null,
            bodyColor: $data['bodyColor'] ?? null,
            bodyColorOriginal: $data['bodyColorOriginal'] ?? null,
            paintType: $data['paintType'] ?? null,
            numberOfSeats: isset($data['numberOfSeats']) ? (int) $data['numberOfSeats'] : null,
            numberOfDoors: isset($data['numberOfDoors']) ? (int) $data['numberOfDoors'] : null,
            powerInKw: $data['powerInKw'] ?? null,
            powerInHp: $data['powerInHp'] ?? null,
            rawPowerInKw: isset($data['rawPowerInKw']) ? (int) $data['rawPowerInKw'] : null,
            rawPowerInHp: isset($data['rawPowerInHp']) ? (int) $data['rawPowerInHp'] : null,
            transmissionType: $data['transmissionType'] ?? null,
            gears: isset($data['gears']) ? (int) $data['gears'] : null,
            cylinders: isset($data['cylinders']) ? (int) $data['cylinders'] : null,
            driveTrain: $data['driveTrain'] ?? null,
            rawDisplacementInCCM: isset($data['rawDisplacementInCCM']) ? (int) $data['rawDisplacementInCCM'] : null,
            weight: $data['weight'] ?? null,
            licensePlate: $data['licensePlate'] ?? null,
            upholstery: $data['upholstery'] ?? null,
            upholsteryColor: $data['upholsteryColor'] ?? null,
            modelGroup: $data['modelGroup'] ?? null,
            variant: $data['variant'] ?? null,
            offerType: $data['offerType'] ?? null,
            fuelCategory: isset($data['fuelCategory']) ? VehicleFuelInfo::fromArray($data['fuelCategory']) : null,
            primaryFuel: isset($data['primaryFuel']) ? VehicleFuelInfo::fromArray($data['primaryFuel']) : null,
            emissionClass: $data['emissionClass'] ?? null,
            co2EmissionCombinedFormatted: $data['co2EmissionCombinedFormatted'] ?? null,
            hasParticleFilter: (bool) ($data['hasParticleFilter'] ?? false),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'makeId' => $this->makeId,
            'modelOrModelLineId' => $this->modelOrModelLineId,
            'make' => $this->make,
            'model' => $this->model,
            'modelId' => $this->modelId,
            'type' => $this->type,
            'mileageInKmRaw' => $this->mileageInKmRaw,
            'mileageInKm' => $this->mileageInKm,
            'firstRegistrationDateRaw' => $this->firstRegistrationDateRaw,
            'firstRegistrationDate' => $this->firstRegistrationDate,
            'bodyType' => $this->bodyType,
            'bodyColor' => $this->bodyColor,
            'bodyColorOriginal' => $this->bodyColorOriginal,
            'paintType' => $this->paintType,
            'numberOfSeats' => $this->numberOfSeats,
            'numberOfDoors' => $this->numberOfDoors,
            'powerInKw' => $this->powerInKw,
            'powerInHp' => $this->powerInHp,
            'rawPowerInKw' => $this->rawPowerInKw,
            'rawPowerInHp' => $this->rawPowerInHp,
            'transmissionType' => $this->transmissionType,
            'gears' => $this->gears,
            'cylinders' => $this->cylinders,
            'driveTrain' => $this->driveTrain,
            'rawDisplacementInCCM' => $this->rawDisplacementInCCM,
            'weight' => $this->weight,
            'licensePlate' => $this->licensePlate,
            'upholstery' => $this->upholstery,
            'upholsteryColor' => $this->upholsteryColor,
            'modelGroup' => $this->modelGroup,
            'variant' => $this->variant,
            'offerType' => $this->offerType,
            'fuelCategory' => $this->fuelCategory?->toArray(),
            'primaryFuel' => $this->primaryFuel?->toArray(),
            'emissionClass' => $this->emissionClass,
            'co2EmissionCombinedFormatted' => $this->co2EmissionCombinedFormatted,
            'hasParticleFilter' => $this->hasParticleFilter,
        ];
    }
}
