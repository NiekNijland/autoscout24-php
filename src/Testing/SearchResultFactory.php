<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Testing;

use NiekNijland\AutoScout24\Data\SearchResult;

class SearchResultFactory
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function make(array $overrides = []): SearchResult
    {
        $listings = $overrides['listings'] ?? ListingFactory::makeMany(3);

        $defaults = [
            'listings' => $listings,
            'totalCount' => count($listings),
            'currentPage' => 1,
            'numberOfPages' => 1,
        ];

        $data = array_merge($defaults, $overrides);

        return new SearchResult(
            listings: $data['listings'],
            totalCount: (int) $data['totalCount'],
            currentPage: (int) $data['currentPage'],
            numberOfPages: (int) $data['numberOfPages'],
        );
    }
}
