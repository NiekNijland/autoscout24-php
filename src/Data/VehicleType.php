<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

enum VehicleType: string
{
    case Car = 'Car';
    case Motorcycle = 'Motorbike';

    public function articleTypeParam(): ?string
    {
        return match ($this) {
            self::Car => null,
            self::Motorcycle => 'B',
        };
    }
}
