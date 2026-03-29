# AutoScout24 PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nieknijland/autoscout24.svg?style=flat-square)](https://packagist.org/packages/nieknijland/autoscout24)
[![Tests](https://img.shields.io/github/actions/workflow/status/nieknijland/autoscout24/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/nieknijland/autoscout24/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/nieknijland/autoscout24.svg?style=flat-square)](https://packagist.org/packages/nieknijland/autoscout24)

A PHP client for AutoScout24.nl that wraps internal Next.js data endpoints into a typed API. Supports searching for cars and motorcycles, fetching listing details, and retrieving filter taxonomy data (brands, models, body types, fuel types, etc.).

## Installation

You can install the package via composer:

```bash
composer require nieknijland/autoscout24-php
```

## Usage

### Search for cars

```php
use NiekNijland\AutoScout24\AutoScout24;
use NiekNijland\AutoScout24\Data\CarSearchCriteria;
use NiekNijland\AutoScout24\Data\Filters\SharedSearchFilters;
use NiekNijland\AutoScout24\Data\SortOrder;

$client = new AutoScout24();

// Basic search with defaults
$result = $client->search(new CarSearchCriteria());

foreach ($result->listings as $listing) {
    echo "{$listing->vehicle->make} {$listing->vehicle->model} - {$listing->price->priceFormatted}\n";
}

// Search with filters
$result = $client->search(CarSearchCriteria::fromFilters(
    shared: new SharedSearchFilters(
        priceFrom: 5000,
        priceTo: 20000,
        sortOrder: SortOrder::PriceAscending,
    ),
));
```

### Search for motorcycles

```php
use NiekNijland\AutoScout24\Data\MotorcycleSearchCriteria;

$result = $client->search(new MotorcycleSearchCriteria());
```

### Auto-paginate through all results

```php
// Lazily iterate through all pages
foreach ($client->searchAll(new CarSearchCriteria(), delayMs: 200) as $listing) {
    echo "{$listing->vehicle->make} {$listing->vehicle->model}\n";
}
```

### Get listing details

```php
$detail = $client->getDetailBySlug('volkswagen-polo-tsi-abc123');
echo $detail->vehicle->make;          // "Volkswagen"
echo $detail->prices->public->price;  // "€ 15.990"
echo $detail->descriptionText();      // Plain text (HTML stripped)
```

### Brands and filter options

```php
use NiekNijland\AutoScout24\Data\VehicleType;

$brands = $client->getBrands(VehicleType::Car);
$models = $client->getModels(VehicleType::Car, $brands[0]);
$bodyTypes = $client->getFilterOptions(VehicleType::Car, 'bodyType');
```

### Caching

Pass a PSR-16 cache to avoid refetching the build ID on every request:

```php
$client = new AutoScout24(
    cache: $yourPsr16Cache,
    cacheTtl: 3600,
);
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [NiekNijland](https://github.com/NiekNijland)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
