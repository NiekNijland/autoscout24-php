<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data\Filters;

use NiekNijland\AutoScout24\Data\Brand;
use NiekNijland\AutoScout24\Data\Model;
use NiekNijland\AutoScout24\Data\OfferType;
use NiekNijland\AutoScout24\Data\PowerType;
use NiekNijland\AutoScout24\Data\SellerType;
use NiekNijland\AutoScout24\Data\SortOrder;

final readonly class SharedSearchFilters
{
    /**
     * @param  OfferType[]  $offerTypes
     */
    public function __construct(
        public ?Brand $brand = null,
        public ?Model $model = null,
        public ?SortOrder $sortOrder = null,
        public array $offerTypes = [],
        public ?string $country = null,
        public ?int $priceFrom = null,
        public ?int $priceTo = null,
        public ?int $yearFrom = null,
        public ?int $yearTo = null,
        public ?int $mileageFrom = null,
        public ?int $mileageTo = null,
        public ?int $ccFrom = null,
        public ?int $ccTo = null,
        public ?int $powerFrom = null,
        public ?int $powerTo = null,
        public ?PowerType $powerType = null,
        public ?SellerType $sellerType = null,
        public ?string $zip = null,
        public ?int $radius = null,
        public ?string $onlineSince = null,
        public bool $excludeDamaged = false,
        public ?string $priceType = null,
    ) {}
}
