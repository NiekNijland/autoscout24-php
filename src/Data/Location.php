<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

readonly class Location
{
    public function __construct(
        public string $countryCode,
        public ?string $zip = null,
        public ?string $city = null,
        public ?string $street = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            countryCode: (string) ($data['countryCode'] ?? ''),
            zip: $data['zip'] ?? null,
            city: $data['city'] ?? null,
            street: $data['street'] ?? null,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'countryCode' => $this->countryCode,
            'zip' => $this->zip,
            'city' => $this->city,
            'street' => $this->street,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
