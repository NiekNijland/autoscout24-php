<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

use NiekNijland\AutoScout24\Data\Concerns\HasSharedQueryParams;
use NiekNijland\AutoScout24\Data\Filters\SharedSearchFilters;
use NiekNijland\AutoScout24\Data\Filters\VehicleSearchFilters;

final readonly class MotorcycleSearchCriteria implements SearchQuery
{
    use HasSharedQueryParams;

    public function __construct(
        public SharedSearchFilters $shared = new SharedSearchFilters,
        public VehicleSearchFilters $filters = new VehicleSearchFilters,
        public int $page = 1,
    ) {}

    public static function fromFilters(
        SharedSearchFilters $shared = new SharedSearchFilters,
        VehicleSearchFilters $filters = new VehicleSearchFilters,
        int $page = 1,
    ): self {
        return new self(shared: $shared, filters: $filters, page: $page);
    }

    public function vehicleType(): VehicleType
    {
        return VehicleType::Motorcycle;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function withPage(int $page): static
    {
        return new self(
            shared: $this->shared,
            filters: $this->filters,
            page: $page,
        );
    }

    public function toQueryParams(): QueryParams
    {
        return $this->buildQueryParams($this->shared, $this->filters, $this->page, atype: 'B');
    }
}
