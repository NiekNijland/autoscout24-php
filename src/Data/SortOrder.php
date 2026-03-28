<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

enum SortOrder: string
{
    case Standard = 'standard';
    case PriceAscending = 'price_asc';
    case PriceDescending = 'price_desc';
    case YearAscending = 'age_asc';
    case YearDescending = 'age_desc';
    case MileageAscending = 'mileage_asc';
    case MileageDescending = 'mileage_desc';
    case PowerAscending = 'power_asc';
    case PowerDescending = 'power_desc';

    public function sortField(): string
    {
        return match ($this) {
            self::Standard => 'standard',
            self::PriceAscending, self::PriceDescending => 'price',
            self::YearAscending, self::YearDescending => 'age',
            self::MileageAscending, self::MileageDescending => 'mileage',
            self::PowerAscending, self::PowerDescending => 'power',
        };
    }

    public function isDescending(): bool
    {
        return match ($this) {
            self::Standard, self::PriceAscending, self::YearAscending, self::MileageAscending, self::PowerAscending => false,
            self::PriceDescending, self::YearDescending, self::MileageDescending, self::PowerDescending => true,
        };
    }
}
