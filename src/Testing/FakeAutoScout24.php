<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Testing;

use Generator;
use NiekNijland\AutoScout24\AutoScout24Interface;
use NiekNijland\AutoScout24\Data\Brand;
use NiekNijland\AutoScout24\Data\FilterOption;
use NiekNijland\AutoScout24\Data\Listing;
use NiekNijland\AutoScout24\Data\ListingDetail;
use NiekNijland\AutoScout24\Data\Model;
use NiekNijland\AutoScout24\Data\SearchQuery;
use NiekNijland\AutoScout24\Data\SearchResult;
use NiekNijland\AutoScout24\Data\VehicleType;
use PHPUnit\Framework\Assert;

class FakeAutoScout24 implements AutoScout24Interface
{
    private ?SearchResult $searchResult = null;

    /** @var SearchResult[] */
    private array $searchResults = [];

    private ?ListingDetail $listingDetail = null;

    /** @var Brand[] */
    private array $brands = [];

    /** @var Model[] */
    private array $models = [];

    /** @var FilterOption[] */
    private array $filterOptions = [];

    private ?\Throwable $exception = null;

    /** @var RecordedCall[] */
    private array $calls = [];

    private bool $sessionReset = false;

    public function withSearchResult(SearchResult $result): self
    {
        $this->searchResult = $result;

        return $this;
    }

    /**
     * Configure multiple search results for simulating multi-page searchAll() behavior.
     *
     * @param  SearchResult[]  $results
     */
    public function withSearchResults(array $results): self
    {
        $this->searchResults = $results;

        return $this;
    }

    public function withListingDetail(ListingDetail $detail): self
    {
        $this->listingDetail = $detail;

        return $this;
    }

    /**
     * @param  Brand[]  $brands
     */
    public function withBrands(array $brands): self
    {
        $this->brands = $brands;

        return $this;
    }

    /**
     * @param  Model[]  $models
     */
    public function withModels(array $models): self
    {
        $this->models = $models;

        return $this;
    }

    /**
     * @param  FilterOption[]  $options
     */
    public function withFilterOptions(array $options): self
    {
        $this->filterOptions = $options;

        return $this;
    }

    public function shouldThrow(\Throwable $exception): self
    {
        $this->exception = $exception;

        return $this;
    }

    public function search(SearchQuery $query): SearchResult
    {
        $this->record('search', [$query]);
        $this->throwIfConfigured();

        return $this->searchResult ?? SearchResultFactory::make();
    }

    /**
     * @return Generator<int, Listing>
     */
    public function searchAll(SearchQuery $query, int $delayMs = 0): Generator
    {
        $this->record('searchAll', [$query]);
        $this->throwIfConfigured();

        foreach ($this->searchResults as $result) {
            yield from $result->listings;
        }

        if ($this->searchResults === []) {
            $result = $this->searchResult ?? SearchResultFactory::make();
            yield from $result->listings;
        }
    }

    /**
     * @return Brand[]
     */
    public function getBrands(VehicleType $type): array
    {
        $this->record('getBrands', [$type]);
        $this->throwIfConfigured();

        return $this->brands !== [] ? $this->brands : BrandFactory::makeMany(3);
    }

    /**
     * @return Model[]
     */
    public function getModels(VehicleType $type, Brand $brand): array
    {
        $this->record('getModels', [$type, $brand]);
        $this->throwIfConfigured();

        return $this->models !== [] ? $this->models : ModelFactory::makeMany(3);
    }

    /**
     * @return FilterOption[]
     */
    public function getFilterOptions(VehicleType $type, string $taxonomyKey): array
    {
        $this->record('getFilterOptions', [$type, $taxonomyKey]);
        $this->throwIfConfigured();

        return $this->filterOptions;
    }

    public function getDetail(Listing $listing): ListingDetail
    {
        $this->record('getDetail', [$listing]);
        $this->throwIfConfigured();

        return $this->listingDetail ?? ListingDetailFactory::make();
    }

    public function getDetailBySlug(string $slug): ListingDetail
    {
        $this->record('getDetailBySlug', [$slug]);
        $this->throwIfConfigured();

        return $this->listingDetail ?? ListingDetailFactory::make();
    }

    public function getDetailByUrl(string $url): ListingDetail
    {
        $this->record('getDetailByUrl', [$url]);
        $this->throwIfConfigured();

        return $this->listingDetail ?? ListingDetailFactory::make();
    }

    public function resetSession(): void
    {
        $this->record('resetSession', []);
        $this->sessionReset = true;
    }

    public function assertCalled(string $method, ?int $times = null): void
    {
        $matches = array_filter($this->calls, static fn (RecordedCall $c): bool => $c->method === $method);

        if ($times !== null) {
            Assert::assertCount($times, $matches, "Expected '{$method}' to be called {$times} time(s), got ".count($matches));
        } else {
            Assert::assertNotEmpty($matches, "Expected '{$method}' to be called at least once");
        }
    }

    public function assertNotCalled(string $method): void
    {
        $matches = array_filter($this->calls, static fn (RecordedCall $c): bool => $c->method === $method);
        Assert::assertEmpty($matches, "Expected '{$method}' to not be called, but it was called ".count($matches).' time(s)');
    }

    public function assertSessionReset(): void
    {
        Assert::assertTrue($this->sessionReset, 'Expected resetSession() to be called');
    }

    /**
     * @return RecordedCall[]
     */
    public function getCalls(): array
    {
        return $this->calls;
    }

    /**
     * @param  array<int, mixed>  $args
     */
    private function record(string $method, array $args): void
    {
        $this->calls[] = new RecordedCall($method, $args);
    }

    private function throwIfConfigured(): void
    {
        if ($this->exception !== null) {
            $exception = $this->exception;
            $this->exception = null;

            throw $exception;
        }
    }
}
