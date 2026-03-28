<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Tests\Integration;

use NiekNijland\AutoScout24\AutoScout24;
use NiekNijland\AutoScout24\Data\Brand;
use NiekNijland\AutoScout24\Data\CarSearchCriteria;
use NiekNijland\AutoScout24\Data\FilterOption;
use NiekNijland\AutoScout24\Data\Filters\SharedSearchFilters;
use NiekNijland\AutoScout24\Data\Listing;
use NiekNijland\AutoScout24\Data\ListingDetail;
use NiekNijland\AutoScout24\Data\Model;
use NiekNijland\AutoScout24\Data\MotorcycleSearchCriteria;
use NiekNijland\AutoScout24\Data\OfferType;
use NiekNijland\AutoScout24\Data\SearchResult;
use NiekNijland\AutoScout24\Data\SortOrder;
use NiekNijland\AutoScout24\Data\VehicleType;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests that hit the live AutoScout24.nl site.
 *
 * Run with: composer test-integration
 */
class IntegrationTest extends TestCase
{
    private static AutoScout24 $client;

    private static SearchResult $carSearchResult;

    private static SearchResult $motorcycleSearchResult;

    /** @var Brand[] */
    private static array $carBrands;

    /** @var Brand[] */
    private static array $motorcycleBrands;

    private static ?ListingDetail $carDetail = null;

    public static function setUpBeforeClass(): void
    {
        self::$client = new AutoScout24;

        // Fetch car search results
        self::$carSearchResult = self::$client->search(new CarSearchCriteria);

        // Fetch motorcycle search results
        self::$motorcycleSearchResult = self::$client->search(new MotorcycleSearchCriteria);

        // Fetch brands
        self::$carBrands = self::$client->getBrands(VehicleType::Car);
        self::$motorcycleBrands = self::$client->getBrands(VehicleType::Motorcycle);

        // Fetch a detail page from the first car listing
        if (count(self::$carSearchResult->listings) > 0) {
            self::$carDetail = self::$client->getDetail(self::$carSearchResult->listings[0]);
        }
    }

    // --- Car Search ---

    public function test_car_search_returns_results(): void
    {
        $this->assertGreaterThan(0, self::$carSearchResult->totalCount);
        $this->assertGreaterThan(0, self::$carSearchResult->numberOfPages);
        $this->assertSame(1, self::$carSearchResult->currentPage);
        $this->assertNotEmpty(self::$carSearchResult->listings);
    }

    public function test_car_search_listings_have_required_fields(): void
    {
        $listing = self::$carSearchResult->listings[0];

        $this->assertNotEmpty($listing->id);
        $this->assertNotEmpty($listing->url);
        $this->assertStringStartsWith('/aanbod/', $listing->url);
        $this->assertNotEmpty($listing->price->priceFormatted);
        $this->assertNotEmpty($listing->vehicle->make);
        $this->assertNotEmpty($listing->vehicle->model);
        $this->assertGreaterThan(0, $listing->vehicle->modelId);
        $this->assertNotEmpty($listing->location->countryCode);
        $this->assertNotEmpty($listing->seller->id);
        $this->assertNotEmpty($listing->seller->type);
    }

    public function test_car_search_listings_have_images(): void
    {
        $listing = self::$carSearchResult->listings[0];
        $this->assertNotEmpty($listing->images);
        $this->assertStringContainsString('autoscout24.net', $listing->images[0]);
    }

    public function test_car_search_listings_have_vehicle_details(): void
    {
        $listing = self::$carSearchResult->listings[0];
        $this->assertNotEmpty($listing->vehicleDetails);

        $detail = $listing->vehicleDetails[0];
        $this->assertNotEmpty($detail->data);
        $this->assertNotEmpty($detail->iconName);
        $this->assertNotEmpty($detail->ariaLabel);
    }

    // --- Motorcycle Search ---

    public function test_motorcycle_search_returns_results(): void
    {
        $this->assertGreaterThan(0, self::$motorcycleSearchResult->totalCount);
        $this->assertNotEmpty(self::$motorcycleSearchResult->listings);
    }

    public function test_motorcycle_search_returns_motorbikes(): void
    {
        $listing = self::$motorcycleSearchResult->listings[0];
        $this->assertSame('Motorbike', $listing->vehicle->articleType);
    }

    // --- Filtered Search ---

    public function test_filtered_car_search(): void
    {
        $criteria = CarSearchCriteria::fromFilters(
            shared: new SharedSearchFilters(
                priceFrom: 5000,
                priceTo: 20000,
                offerTypes: [OfferType::Used],
                sortOrder: SortOrder::PriceAscending,
            ),
        );

        $result = self::$client->search($criteria);

        $this->assertGreaterThan(0, $result->totalCount);
        $this->assertNotEmpty($result->listings);
    }

    public function test_search_with_brand_filter(): void
    {
        // Find Volkswagen in the brands list
        $volkswagen = null;
        foreach (self::$carBrands as $brand) {
            if (stripos($brand->label, 'Volkswagen') !== false) {
                $volkswagen = $brand;
                break;
            }
        }

        if ($volkswagen === null) {
            $this->markTestSkipped('Volkswagen not found in brands');
        }

        $criteria = CarSearchCriteria::fromFilters(
            shared: new SharedSearchFilters(brand: $volkswagen),
        );

        $result = self::$client->search($criteria);
        $this->assertGreaterThan(0, $result->totalCount);

        // All results should be Volkswagen
        foreach ($result->listings as $listing) {
            $this->assertSame('Volkswagen', $listing->vehicle->make);
        }
    }

    // --- Pagination ---

    public function test_search_pagination(): void
    {
        $page1 = self::$carSearchResult;
        $this->assertTrue($page1->hasNextPage());

        $criteria = (new CarSearchCriteria)->withPage(2);
        $page2 = self::$client->search($criteria);

        $this->assertSame(2, $page2->currentPage);
        $this->assertNotEmpty($page2->listings);

        // Pages should have mostly different listings (promoted listings may overlap)
        $page1Ids = array_map(static fn (Listing $l): string => $l->id, $page1->listings);
        $page2Ids = array_map(static fn (Listing $l): string => $l->id, $page2->listings);
        $overlap = array_intersect($page1Ids, $page2Ids);
        $this->assertLessThan(count($page1Ids), count($overlap), 'Pages should not be completely identical');
    }

    public function test_search_all_yields_multiple_pages(): void
    {
        $criteria = CarSearchCriteria::fromFilters(
            shared: new SharedSearchFilters(
                priceFrom: 50000,
                priceTo: 55000,
                offerTypes: [OfferType::Used],
            ),
        );

        $count = 0;
        $maxListings = 25; // Just verify we get more than 1 page worth
        foreach (self::$client->searchAll($criteria) as $listing) {
            $this->assertInstanceOf(Listing::class, $listing);
            $count++;
            if ($count >= $maxListings) {
                break;
            }
        }

        $this->assertGreaterThan(20, $count, 'Expected more than 1 page of results');
    }

    // --- Brands ---

    public function test_car_brands_available(): void
    {
        $this->assertNotEmpty(self::$carBrands);
        $this->assertContainsOnlyInstancesOf(Brand::class, self::$carBrands);

        // Check for well-known brands
        $labels = array_map(static fn (Brand $b): string => $b->label, self::$carBrands);
        $this->assertContains('Volkswagen', $labels);
        $this->assertContains('BMW', $labels);
        $this->assertContains('Mercedes-Benz', $labels);
    }

    public function test_motorcycle_brands_available(): void
    {
        $this->assertNotEmpty(self::$motorcycleBrands);
        $this->assertContainsOnlyInstancesOf(Brand::class, self::$motorcycleBrands);

        $labels = array_map(static fn (Brand $b): string => $b->label, self::$motorcycleBrands);
        $this->assertContains('Honda', $labels);
        $this->assertContains('Yamaha', $labels);
    }

    // --- Models ---

    public function test_car_models_for_brand(): void
    {
        // Find BMW
        $bmw = null;
        foreach (self::$carBrands as $brand) {
            if ($brand->label === 'BMW') {
                $bmw = $brand;
                break;
            }
        }

        if ($bmw === null) {
            $this->markTestSkipped('BMW not found in brands');
        }

        $models = self::$client->getModels(VehicleType::Car, $bmw);

        $this->assertNotEmpty($models);
        $this->assertContainsOnlyInstancesOf(Model::class, $models);

        // All models should belong to BMW
        foreach ($models as $model) {
            $this->assertSame($bmw->value, $model->makeId);
        }
    }

    // --- Filter Options ---

    public function test_car_body_types_available(): void
    {
        $bodyTypes = self::$client->getFilterOptions(VehicleType::Car, 'bodyType');

        $this->assertNotEmpty($bodyTypes);
        $this->assertContainsOnlyInstancesOf(FilterOption::class, $bodyTypes);
    }

    public function test_car_fuel_types_available(): void
    {
        $fuelTypes = self::$client->getFilterOptions(VehicleType::Car, 'fuelType');

        $this->assertNotEmpty($fuelTypes);
    }

    public function test_car_gearing_options_available(): void
    {
        $gearing = self::$client->getFilterOptions(VehicleType::Car, 'gearing');

        $this->assertNotEmpty($gearing);
    }

    public function test_motorcycle_body_types_available(): void
    {
        $bodyTypes = self::$client->getFilterOptions(VehicleType::Motorcycle, 'bodyType');

        $this->assertNotEmpty($bodyTypes);
    }

    // --- Detail ---

    public function test_car_detail_has_required_fields(): void
    {
        if (self::$carDetail === null) {
            $this->markTestSkipped('No car listing available for detail test');
        }

        $this->assertNotEmpty(self::$carDetail->id);
        $this->assertNotEmpty(self::$carDetail->vehicle->make);
        $this->assertNotEmpty(self::$carDetail->vehicle->model);
        $this->assertGreaterThan(0, self::$carDetail->vehicle->makeId);
        $this->assertGreaterThan(0, self::$carDetail->vehicle->modelId);
        $this->assertNotEmpty(self::$carDetail->seller->id);
        $this->assertNotEmpty(self::$carDetail->location->countryCode);
    }

    public function test_car_detail_has_pricing(): void
    {
        if (self::$carDetail === null) {
            $this->markTestSkipped('No car listing available for detail test');
        }

        $this->assertNotEmpty(self::$carDetail->price->priceFormatted);
        $this->assertNotNull(self::$carDetail->prices->public);
        $this->assertGreaterThan(0, self::$carDetail->prices->public->priceRaw);
    }

    public function test_car_detail_has_images(): void
    {
        if (self::$carDetail === null) {
            $this->markTestSkipped('No car listing available for detail test');
        }

        $this->assertNotEmpty(self::$carDetail->images);
    }

    public function test_car_detail_has_vehicle_specs(): void
    {
        if (self::$carDetail === null) {
            $this->markTestSkipped('No car listing available for detail test');
        }

        $vehicle = self::$carDetail->vehicle;
        $this->assertIsInt($vehicle->mileageInKmRaw);
        $this->assertNotEmpty($vehicle->mileageInKm);
    }

    public function test_get_detail_by_slug(): void
    {
        if (count(self::$carSearchResult->listings) === 0) {
            $this->markTestSkipped('No car listings available');
        }

        $listing = self::$carSearchResult->listings[0];
        // Extract slug from /aanbod/xxx
        $slug = substr($listing->url, 8); // strip "/aanbod/"

        $detail = self::$client->getDetailBySlug($slug);
        $this->assertSame($listing->id, $detail->id);
    }

    public function test_get_detail_by_url(): void
    {
        if (count(self::$carSearchResult->listings) === 0) {
            $this->markTestSkipped('No car listings available');
        }

        $listing = self::$carSearchResult->listings[0];
        $fullUrl = 'https://www.autoscout24.nl'.$listing->url;

        $detail = self::$client->getDetailByUrl($fullUrl);
        $this->assertSame($listing->id, $detail->id);
    }

    // --- Session Reset ---

    public function test_reset_session_and_recover(): void
    {
        $client = new AutoScout24;

        // First search to populate build ID
        $result1 = $client->search(new CarSearchCriteria);
        $this->assertNotEmpty($result1->listings);

        // Reset session
        $client->resetSession();

        // Second search should recover by fetching a new build ID
        $result2 = $client->search(new CarSearchCriteria);
        $this->assertNotEmpty($result2->listings);
    }
}
