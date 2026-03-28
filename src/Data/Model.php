<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

readonly class Model
{
    public function __construct(
        public int $value,
        public string $label,
        public int $makeId,
        public ?int $modelLineId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            value: (int) $data['value'],
            label: (string) $data['label'],
            makeId: (int) $data['makeId'],
            modelLineId: isset($data['modelLineId']) ? (int) $data['modelLineId'] : null,
        );
    }

    /**
     * @return array{value: int, label: string, makeId: int, modelLineId: int|null}
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label,
            'makeId' => $this->makeId,
            'modelLineId' => $this->modelLineId,
        ];
    }
}
