<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Tests\Testing;

use NiekNijland\AutoScout24\Data\Brand;
use NiekNijland\AutoScout24\Data\DetailVehicle;
use NiekNijland\AutoScout24\Data\Listing;
use NiekNijland\AutoScout24\Data\ListingDetail;
use NiekNijland\AutoScout24\Data\Location;
use NiekNijland\AutoScout24\Data\Model;
use NiekNijland\AutoScout24\Data\SearchResult;
use NiekNijland\AutoScout24\Data\Seller;
use NiekNijland\AutoScout24\Data\Vehicle;
use NiekNijland\AutoScout24\Testing\BrandFactory;
use NiekNijland\AutoScout24\Testing\DetailVehicleFactory;
use NiekNijland\AutoScout24\Testing\ListingDetailFactory;
use NiekNijland\AutoScout24\Testing\ListingFactory;
use NiekNijland\AutoScout24\Testing\LocationFactory;
use NiekNijland\AutoScout24\Testing\ModelFactory;
use NiekNijland\AutoScout24\Testing\SearchResultFactory;
use NiekNijland\AutoScout24\Testing\SellerFactory;
use NiekNijland\AutoScout24\Testing\VehicleFactory;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    // --- Brand Factory ---

    public function test_brand_factory_makes_brand(): void
    {
        $brand = BrandFactory::make();

        $this->assertInstanceOf(Brand::class, $brand);
        $this->assertSame(68, $brand->value);
        $this->assertSame('Suzuki', $brand->label);
    }

    public function test_brand_factory_with_overrides(): void
    {
        $brand = BrandFactory::make(['value' => 74, 'label' => 'Volkswagen']);

        $this->assertSame(74, $brand->value);
        $this->assertSame('Volkswagen', $brand->label);
    }

    public function test_brand_factory_makes_many(): void
    {
        $brands = BrandFactory::makeMany(5);

        $this->assertCount(5, $brands);
        $this->assertContainsOnlyInstancesOf(Brand::class, $brands);

        // Each should have unique value
        $values = array_map(static fn (Brand $b): int => $b->value, $brands);
        $this->assertSame(count($values), count(array_unique($values)));
    }

    // --- Model Factory ---

    public function test_model_factory_makes_model(): void
    {
        $model = ModelFactory::make();

        $this->assertInstanceOf(Model::class, $model);
        $this->assertSame(77700, $model->value);
        $this->assertSame(68, $model->makeId);
    }

    public function test_model_factory_makes_many(): void
    {
        $models = ModelFactory::makeMany(3);

        $this->assertCount(3, $models);
        $this->assertContainsOnlyInstancesOf(Model::class, $models);
    }

    // --- Location Factory ---

    public function test_location_factory_makes_location(): void
    {
        $location = LocationFactory::make();

        $this->assertInstanceOf(Location::class, $location);
        $this->assertSame('NL', $location->countryCode);
    }

    // --- Seller Factory ---

    public function test_seller_factory_makes_seller(): void
    {
        $seller = SellerFactory::make();

        $this->assertInstanceOf(Seller::class, $seller);
        $this->assertSame('Dealer', $seller->type);
        $this->assertNotEmpty($seller->phones);
    }

    // --- Vehicle Factory ---

    public function test_vehicle_factory_makes_vehicle(): void
    {
        $vehicle = VehicleFactory::make();

        $this->assertInstanceOf(Vehicle::class, $vehicle);
        $this->assertSame('Volkswagen', $vehicle->make);
        $this->assertSame('Golf', $vehicle->model);
    }

    public function test_vehicle_factory_with_overrides(): void
    {
        $vehicle = VehicleFactory::make(['make' => 'BMW', 'model' => '3 Series']);

        $this->assertSame('BMW', $vehicle->make);
        $this->assertSame('3 Series', $vehicle->model);
    }

    // --- Detail Vehicle Factory ---

    public function test_detail_vehicle_factory_makes_detail_vehicle(): void
    {
        $vehicle = DetailVehicleFactory::make();

        $this->assertInstanceOf(DetailVehicle::class, $vehicle);
        $this->assertSame('Volkswagen', $vehicle->make);
        $this->assertSame(110, $vehicle->rawPowerInKw);
    }

    // --- Listing Factory ---

    public function test_listing_factory_makes_listing(): void
    {
        $listing = ListingFactory::make();

        $this->assertInstanceOf(Listing::class, $listing);
        $this->assertNotEmpty($listing->id);
        $this->assertNotEmpty($listing->url);
        $this->assertNotEmpty($listing->vehicle->make);
    }

    public function test_listing_factory_makes_many(): void
    {
        $listings = ListingFactory::makeMany(5);

        $this->assertCount(5, $listings);
        $this->assertContainsOnlyInstancesOf(Listing::class, $listings);

        $ids = array_map(static fn (Listing $l): string => $l->id, $listings);
        $this->assertSame(count($ids), count(array_unique($ids)));
    }

    // --- Listing Detail Factory ---

    public function test_listing_detail_factory_makes_detail(): void
    {
        $detail = ListingDetailFactory::make();

        $this->assertInstanceOf(ListingDetail::class, $detail);
        $this->assertNotEmpty($detail->id);
        $this->assertNotNull($detail->description);
        $this->assertNotNull($detail->descriptionText());
    }

    // --- Search Result Factory ---

    public function test_search_result_factory_makes_result(): void
    {
        $result = SearchResultFactory::make();

        $this->assertInstanceOf(SearchResult::class, $result);
        $this->assertNotEmpty($result->listings);
        $this->assertSame(count($result->listings), $result->totalCount);
    }

    public function test_search_result_factory_with_overrides(): void
    {
        $result = SearchResultFactory::make([
            'totalCount' => 100,
            'currentPage' => 2,
            'numberOfPages' => 5,
        ]);

        $this->assertSame(100, $result->totalCount);
        $this->assertSame(2, $result->currentPage);
        $this->assertSame(5, $result->numberOfPages);
        $this->assertTrue($result->hasNextPage());
    }
}
