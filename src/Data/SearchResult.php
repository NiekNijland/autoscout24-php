<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Data;

readonly class SearchResult
{
    /**
     * @param  Listing[]  $listings
     */
    public function __construct(
        public array $listings,
        public int $totalCount,
        public int $currentPage,
        public int $numberOfPages,
    ) {}

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->numberOfPages;
    }

    public function pageSize(): int
    {
        return count($this->listings);
    }

    /**
     * Reconstruct a SearchResult from a serialized array (e.g. from cache or test fixtures).
     *
     * Note: The parser constructs SearchResult directly. This method exists for
     * deserialization symmetry with toArray() and for use in test factories.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $listings = array_map(
            static fn (array $listing): Listing => Listing::fromArray($listing),
            $data['listings'] ?? [],
        );

        return new self(
            listings: $listings,
            totalCount: (int) ($data['totalCount'] ?? 0),
            currentPage: (int) ($data['currentPage'] ?? 1),
            numberOfPages: (int) ($data['numberOfPages'] ?? 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'listings' => array_map(static fn (Listing $l): array => $l->toArray(), $this->listings),
            'totalCount' => $this->totalCount,
            'currentPage' => $this->currentPage,
            'numberOfPages' => $this->numberOfPages,
        ];
    }
}
