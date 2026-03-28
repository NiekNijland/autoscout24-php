<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Tests;

use NiekNijland\AutoScout24\Data\Brand;
use NiekNijland\AutoScout24\Data\CarSearchCriteria;
use NiekNijland\AutoScout24\Data\FilterOption;
use NiekNijland\AutoScout24\Data\Filters\SharedSearchFilters;
use NiekNijland\AutoScout24\Data\Filters\VehicleSearchFilters;
use NiekNijland\AutoScout24\Data\Model;
use NiekNijland\AutoScout24\Data\MotorcycleSearchCriteria;
use NiekNijland\AutoScout24\Data\OfferType;
use NiekNijland\AutoScout24\Data\PowerType;
use NiekNijland\AutoScout24\Data\SellerType;
use NiekNijland\AutoScout24\Data\SortOrder;
use NiekNijland\AutoScout24\Data\VehicleType;
use PHPUnit\Framework\TestCase;

class SearchCriteriaTest extends TestCase
{
    // --- Car Search Criteria ---

    public function test_car_criteria_defaults(): void
    {
        $criteria = new CarSearchCriteria;
        $params = $criteria->toQueryParams()->toArray();

        $this->assertSame('standard', $params['sort']);
        $this->assertSame('0', $params['desc']);
        $this->assertSame('NL', $params['cy']);
        $this->assertSame('kw', $params['powertype']);
        $this->assertArrayNotHasKey('atype', $params);
        $this->assertArrayNotHasKey('mmmv', $params);
    }

    public function test_car_criteria_vehicle_type(): void
    {
        $criteria = new CarSearchCriteria;
        $this->assertSame(VehicleType::Car, $criteria->vehicleType());
    }

    public function test_car_criteria_with_brand_and_model(): void
    {
        $brand = new Brand(74, 'Volkswagen');
        $model = new Model(12345, 'Golf', 74, 999);

        $criteria = CarSearchCriteria::fromFilters(
            shared: new SharedSearchFilters(brand: $brand, model: $model),
        );

        $params = $criteria->toQueryParams()->toArray();
        $this->assertSame('74|12345|999|', $params['mmmv']);
    }

    public function test_car_criteria_with_price_range(): void
    {
        $criteria = CarSearchCriteria::fromFilters(
            shared: new SharedSearchFilters(priceFrom: 5000, priceTo: 20000),
        );

        $params = $criteria->toQueryParams()->toArray();
        $this->assertSame(5000, $params['pricefrom']);
        $this->assertSame(20000, $params['priceto']);
    }

    public function test_car_criteria_with_sort_descending(): void
    {
        $criteria = CarSearchCriteria::fromFilters(
            shared: new SharedSearchFilters(sortOrder: SortOrder::PriceDescending),
        );

        $params = $criteria->toQueryParams()->toArray();
        $this->assertSame('price', $params['sort']);
        $this->assertSame('1', $params['desc']);
    }

    public function test_car_criteria_with_offer_types(): void
    {
        $criteria = CarSearchCriteria::fromFilters(
            shared: new SharedSearchFilters(offerTypes: [OfferType::New, OfferType::Used]),
        );

        $params = $criteria->toQueryParams()->toArray();
        $this->assertSame('N,U', $params['ustate']);
    }

    public function test_car_criteria_with_body_types(): void
    {
        $criteria = CarSearchCriteria::fromFilters(
            filters: new VehicleSearchFilters(
                bodyTypes: [new FilterOption(101, 'Hatchback'), new FilterOption(102, 'Sedan')],
            ),
        );

        $params = $criteria->toQueryParams()->toArray();
        $this->assertSame('101,102', $params['body']);
    }

    public function test_car_criteria_with_seller_type(): void
    {
        $criteria = CarSearchCriteria::fromFilters(
            shared: new SharedSearchFilters(sellerType: SellerType::Dealer),
        );

        $params = $criteria->toQueryParams()->toArray();
        $this->assertSame('D', $params['custtype']);
    }

    public function test_car_criteria_with_exclude_damaged(): void
    {
        $criteria = CarSearchCriteria::fromFilters(
            shared: new SharedSearchFilters(excludeDamaged: true),
        );

        $params = $criteria->toQueryParams()->toArray();
        $this->assertSame('exclude', $params['damaged_listing']);
    }

    public function test_car_criteria_with_page(): void
    {
        $criteria = CarSearchCriteria::fromFilters(page: 3);
        $params = $criteria->toQueryParams()->toArray();

        $this->assertSame(3, $params['page']);
        $this->assertSame(3, $criteria->page());
    }

    public function test_car_criteria_with_page_immutability(): void
    {
        $original = new CarSearchCriteria;
        $paginated = $original->withPage(5);

        $this->assertSame(1, $original->page());
        $this->assertSame(5, $paginated->page());
        $this->assertNotSame($original, $paginated);
    }

    public function test_car_criteria_page_1_omitted_from_params(): void
    {
        $criteria = CarSearchCriteria::fromFilters(page: 1);
        $params = $criteria->toQueryParams()->toArray();

        $this->assertArrayNotHasKey('page', $params);
    }

    // --- Motorcycle Search Criteria ---

    public function test_motorcycle_criteria_sets_atype(): void
    {
        $criteria = new MotorcycleSearchCriteria;
        $params = $criteria->toQueryParams()->toArray();

        $this->assertSame('B', $params['atype']);
    }

    public function test_motorcycle_criteria_vehicle_type(): void
    {
        $criteria = new MotorcycleSearchCriteria;
        $this->assertSame(VehicleType::Motorcycle, $criteria->vehicleType());
    }

    public function test_motorcycle_criteria_with_filters(): void
    {
        $criteria = MotorcycleSearchCriteria::fromFilters(
            shared: new SharedSearchFilters(priceFrom: 3000, powerType: PowerType::Hp),
            filters: new VehicleSearchFilters(
                bodyTypes: [new FilterOption(201, 'Supermotard')],
            ),
            page: 2,
        );

        $params = $criteria->toQueryParams()->toArray();
        $this->assertSame('B', $params['atype']);
        $this->assertSame(3000, $params['pricefrom']);
        $this->assertSame('hp', $params['powertype']);
        $this->assertSame('201', $params['body']);
        $this->assertSame(2, $params['page']);
    }

    public function test_motorcycle_criteria_with_page_immutability(): void
    {
        $original = new MotorcycleSearchCriteria;
        $paginated = $original->withPage(3);

        $this->assertSame(1, $original->page());
        $this->assertSame(3, $paginated->page());
    }

    public function test_motorcycle_criteria_with_cc_range(): void
    {
        $criteria = MotorcycleSearchCriteria::fromFilters(
            shared: new SharedSearchFilters(ccFrom: 250, ccTo: 1000),
        );

        $params = $criteria->toQueryParams()->toArray();
        $this->assertSame(250, $params['ccfrom']);
        $this->assertSame(1000, $params['ccto']);
    }

    public function test_motorcycle_criteria_with_fuel_types(): void
    {
        $criteria = MotorcycleSearchCriteria::fromFilters(
            filters: new VehicleSearchFilters(
                fuelTypes: [new FilterOption('E', 'Elektro')],
            ),
        );

        $params = $criteria->toQueryParams()->toArray();
        $this->assertSame('E', $params['fuel']);
    }

    public function test_motorcycle_criteria_with_price_type(): void
    {
        $criteria = MotorcycleSearchCriteria::fromFilters(
            shared: new SharedSearchFilters(priceType: 'N'),
        );

        $params = $criteria->toQueryParams()->toArray();
        $this->assertSame('N', $params['pricetype']);
    }

    public function test_motorcycle_criteria_cc_omitted_when_null(): void
    {
        $criteria = new MotorcycleSearchCriteria;
        $params = $criteria->toQueryParams()->toArray();

        $this->assertArrayNotHasKey('ccfrom', $params);
        $this->assertArrayNotHasKey('ccto', $params);
    }

    public function test_motorcycle_criteria_pricetype_omitted_when_null(): void
    {
        $criteria = new MotorcycleSearchCriteria;
        $params = $criteria->toQueryParams()->toArray();

        $this->assertArrayNotHasKey('pricetype', $params);
    }

    // --- Sort Order Enum ---

    public function test_sort_order_fields(): void
    {
        $this->assertSame('standard', SortOrder::Standard->sortField());
        $this->assertSame('price', SortOrder::PriceAscending->sortField());
        $this->assertSame('price', SortOrder::PriceDescending->sortField());
        $this->assertSame('age', SortOrder::YearAscending->sortField());
        $this->assertSame('mileage', SortOrder::MileageDescending->sortField());
        $this->assertSame('power', SortOrder::PowerAscending->sortField());
    }

    public function test_sort_order_descending(): void
    {
        $this->assertFalse(SortOrder::Standard->isDescending());
        $this->assertFalse(SortOrder::PriceAscending->isDescending());
        $this->assertTrue(SortOrder::PriceDescending->isDescending());
        $this->assertFalse(SortOrder::YearAscending->isDescending());
        $this->assertTrue(SortOrder::YearDescending->isDescending());
    }

    // --- Vehicle Type Enum ---

    public function test_vehicle_type_article_type_param(): void
    {
        $this->assertNull(VehicleType::Car->articleTypeParam());
        $this->assertSame('B', VehicleType::Motorcycle->articleTypeParam());
    }
}
