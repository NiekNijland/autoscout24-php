<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

interface SearchQuery
{
    public function vehicleType(): VehicleType;

    public function toQueryParams(): QueryParams;

    public function page(): int;

    public function withPage(int $page): static;
}
