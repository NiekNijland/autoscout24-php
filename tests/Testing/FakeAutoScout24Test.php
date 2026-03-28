<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Tests\Testing;

use NiekNijland\AutoScout24\Data\CarSearchCriteria;
use NiekNijland\AutoScout24\Data\VehicleType;
use NiekNijland\AutoScout24\Exception\AutoScout24Exception;
use NiekNijland\AutoScout24\Testing\BrandFactory;
use NiekNijland\AutoScout24\Testing\FakeAutoScout24;
use NiekNijland\AutoScout24\Testing\ListingDetailFactory;
use NiekNijland\AutoScout24\Testing\ListingFactory;
use NiekNijland\AutoScout24\Testing\SearchResultFactory;
use PHPUnit\Framework\TestCase;

class FakeAutoScout24Test extends TestCase
{
    public function test_returns_default_search_result(): void
    {
        $fake = new FakeAutoScout24;
        $result = $fake->search(new CarSearchCriteria);

        $this->assertNotEmpty($result->listings);
    }

    public function test_returns_configured_search_result(): void
    {
        $fake = new FakeAutoScout24;
        $expected = SearchResultFactory::make(['totalCount' => 42]);
        $fake->withSearchResult($expected);

        $result = $fake->search(new CarSearchCriteria);
        $this->assertSame(42, $result->totalCount);
    }

    public function test_returns_default_listing_detail(): void
    {
        $fake = new FakeAutoScout24;
        $detail = $fake->getDetailBySlug('test-slug');

        $this->assertNotEmpty($detail->id);
    }

    public function test_returns_configured_listing_detail(): void
    {
        $fake = new FakeAutoScout24;
        $expected = ListingDetailFactory::make(['id' => 'custom-id']);
        $fake->withListingDetail($expected);

        $detail = $fake->getDetailBySlug('test');
        $this->assertSame('custom-id', $detail->id);
    }

    public function test_returns_default_brands(): void
    {
        $fake = new FakeAutoScout24;
        $brands = $fake->getBrands(VehicleType::Car);

        $this->assertCount(3, $brands);
    }

    public function test_returns_configured_brands(): void
    {
        $brands = BrandFactory::makeMany(5);
        $fake = new FakeAutoScout24;
        $fake->withBrands($brands);

        $result = $fake->getBrands(VehicleType::Car);
        $this->assertCount(5, $result);
    }

    public function test_records_calls(): void
    {
        $fake = new FakeAutoScout24;
        $fake->search(new CarSearchCriteria);
        $fake->search(new CarSearchCriteria);

        $fake->assertCalled('search', times: 2);
    }

    public function test_assert_not_called(): void
    {
        $fake = new FakeAutoScout24;
        $fake->assertNotCalled('search');
    }

    public function test_assert_session_reset(): void
    {
        $fake = new FakeAutoScout24;
        $fake->resetSession();

        $fake->assertSessionReset();
    }

    public function test_one_shot_exception(): void
    {
        $fake = new FakeAutoScout24;
        $fake->shouldThrow(new AutoScout24Exception('Test error'));

        $this->expectException(AutoScout24Exception::class);
        $fake->search(new CarSearchCriteria);
    }

    public function test_one_shot_exception_fires_once(): void
    {
        $fake = new FakeAutoScout24;
        $fake->shouldThrow(new AutoScout24Exception('Test error'));

        try {
            $fake->search(new CarSearchCriteria);
        } catch (AutoScout24Exception) {
            // Expected
        }

        // Second call should work
        $result = $fake->search(new CarSearchCriteria);
        $this->assertNotEmpty($result->listings);
    }

    public function test_search_all_yields_listings(): void
    {
        $fake = new FakeAutoScout24;
        $listings = iterator_to_array($fake->searchAll(new CarSearchCriteria));

        $this->assertNotEmpty($listings);
    }

    public function test_get_detail_from_listing(): void
    {
        $fake = new FakeAutoScout24;
        $listing = ListingFactory::make();
        $detail = $fake->getDetail($listing);

        $this->assertNotEmpty($detail->id);
        $fake->assertCalled('getDetail', times: 1);
    }

    public function test_get_detail_by_url(): void
    {
        $fake = new FakeAutoScout24;
        $detail = $fake->getDetailByUrl('https://www.autoscout24.nl/aanbod/test');

        $this->assertNotEmpty($detail->id);
        $fake->assertCalled('getDetailByUrl', times: 1);
    }

    public function test_search_all_with_multiple_pages(): void
    {
        $fake = new FakeAutoScout24;

        $page1 = SearchResultFactory::make([
            'listings' => ListingFactory::makeMany(2),
            'totalCount' => 4,
            'currentPage' => 1,
            'numberOfPages' => 2,
        ]);
        $page2 = SearchResultFactory::make([
            'listings' => ListingFactory::makeMany(2),
            'totalCount' => 4,
            'currentPage' => 2,
            'numberOfPages' => 2,
        ]);

        $fake->withSearchResults([$page1, $page2]);

        $listings = iterator_to_array($fake->searchAll(new CarSearchCriteria), false);
        $this->assertCount(4, $listings);
    }
}
