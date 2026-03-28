<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

readonly class Vehicle
{
    public function __construct(
        public string $articleType,
        public string $type,
        public string $make,
        public string $model,
        public int $modelId,
        public string $offerType,
        public string $mileageInKm,
        public ?int $mileageInKmRaw = null,
        public ?string $modelGroup = null,
        public ?string $variant = null,
        public ?string $subtitle = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $mileageInKm = (string) ($data['mileageInKm'] ?? '');
        $mileageRaw = isset($data['mileageInKmRaw']) ? (int) $data['mileageInKmRaw'] : null;

        // Parse raw mileage from the formatted string if not provided.
        if ($mileageRaw === null && $mileageInKm !== '') {
            $digits = preg_replace('/[^\d]/', '', $mileageInKm);
            if ($digits !== '' && $digits !== '0') {
                $mileageRaw = (int) $digits;
            }
        }

        return new self(
            articleType: (string) ($data['articleType'] ?? $data['type'] ?? ''),
            type: (string) ($data['type'] ?? ''),
            make: (string) ($data['make'] ?? ''),
            model: (string) ($data['model'] ?? ''),
            modelId: (int) ($data['modelId'] ?? 0),
            offerType: (string) ($data['offerType'] ?? ''),
            mileageInKm: $mileageInKm,
            mileageInKmRaw: $mileageRaw,
            modelGroup: $data['modelGroup'] ?? null,
            variant: $data['variant'] ?? null,
            subtitle: $data['subtitle'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'articleType' => $this->articleType,
            'type' => $this->type,
            'make' => $this->make,
            'model' => $this->model,
            'modelId' => $this->modelId,
            'offerType' => $this->offerType,
            'mileageInKm' => $this->mileageInKm,
            'mileageInKmRaw' => $this->mileageInKmRaw,
            'modelGroup' => $this->modelGroup,
            'variant' => $this->variant,
            'subtitle' => $this->subtitle,
        ];
    }
}
