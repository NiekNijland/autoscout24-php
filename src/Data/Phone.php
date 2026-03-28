<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

readonly class Phone
{
    public function __construct(
        public string $phoneType,
        public string $formattedNumber,
        public string $callTo,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            phoneType: (string) ($data['phoneType'] ?? ''),
            formattedNumber: (string) ($data['formattedNumber'] ?? ''),
            callTo: (string) ($data['callTo'] ?? ''),
        );
    }

    /**
     * @return array{phoneType: string, formattedNumber: string, callTo: string}
     */
    public function toArray(): array
    {
        return [
            'phoneType' => $this->phoneType,
            'formattedNumber' => $this->formattedNumber,
            'callTo' => $this->callTo,
        ];
    }
}
