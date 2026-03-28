<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

readonly class VehicleFuelInfo
{
    public function __construct(
        public ?string $raw = null,
        public ?string $formatted = null,
    ) {}

    /**
     * @param  array{raw?: string|null, formatted?: string|null}  $data
     */
    public static function fromArray(array $data): self
    {
        $raw = $data['raw'] ?? null;

        return new self(
            raw: $raw !== null ? (string) $raw : null,
            formatted: isset($data['formatted']) ? (string) $data['formatted'] : null,
        );
    }

    /**
     * @return array{raw: string|null, formatted: string|null}
     */
    public function toArray(): array
    {
        return [
            'raw' => $this->raw,
            'formatted' => $this->formatted,
        ];
    }
}
