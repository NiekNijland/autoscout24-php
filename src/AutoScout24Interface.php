<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24;

use Generator;
use NiekNijland\AutoScout24\Data\Brand;
use NiekNijland\AutoScout24\Data\FilterOption;
use NiekNijland\AutoScout24\Data\Listing;
use NiekNijland\AutoScout24\Data\ListingDetail;
use NiekNijland\AutoScout24\Data\Model;
use NiekNijland\AutoScout24\Data\SearchQuery;
use NiekNijland\AutoScout24\Data\SearchResult;
use NiekNijland\AutoScout24\Data\VehicleType;

interface AutoScout24Interface
{
    /** Search for vehicle listings. */
    public function search(SearchQuery $query): SearchResult;

    /**
     * Auto-paginate through all pages, yielding each Listing lazily.
     *
     * @param  int  $delayMs  Milliseconds to sleep between page requests (0 = no delay)
     * @return Generator<int, Listing>
     */
    public function searchAll(SearchQuery $query, int $delayMs = 0): Generator;

    /**
     * Get available brands for a vehicle type.
     *
     * @return Brand[]
     */
    public function getBrands(VehicleType $type): array;

    /**
     * Get available models for a brand within a vehicle type.
     *
     * @return Model[]
     */
    public function getModels(VehicleType $type, Brand $brand): array;

    /**
     * Get available filter options from taxonomy (body types, fuel types, etc.).
     *
     * @return FilterOption[]
     */
    public function getFilterOptions(VehicleType $type, string $taxonomyKey): array;

    /** Get full detail for a listing. */
    public function getDetail(Listing $listing): ListingDetail;

    /** Get full detail by URL slug. */
    public function getDetailBySlug(string $slug): ListingDetail;

    /** Get full detail by full URL. */
    public function getDetailByUrl(string $url): ListingDetail;

    /** Force-refresh the buildId. */
    public function resetSession(): void;
}
