<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data\Filters;

use NiekNijland\AutoScout24\Data\FilterOption;

final readonly class VehicleSearchFilters
{
    /**
     * @param  FilterOption[]  $bodyTypes
     * @param  FilterOption[]  $fuelTypes
     * @param  FilterOption[]  $gearTypes
     * @param  FilterOption[]  $colors
     * @param  FilterOption[]  $equipment
     */
    public function __construct(
        public array $bodyTypes = [],
        public array $fuelTypes = [],
        public array $gearTypes = [],
        public array $colors = [],
        public array $equipment = [],
    ) {}
}
