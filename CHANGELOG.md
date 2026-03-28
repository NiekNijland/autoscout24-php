# Changelog

All notable changes to `AutoScout24` will be documented in this file.

## v0.1.0 - 2026-03-28

### Initial Release

Typed PHP 8.4 wrapper around AutoScout24.nl's internal Next.js data endpoints.

#### Features

- **Car & motorcycle search** — paginated search with full filter support (brand, model, price range, mileage, fuel type, body type, etc.)
- **Listing details** — fetch full listing data including seller info, vehicle specs, and pricing
- **Filter taxonomy** — retrieve available brands, models, body types, fuel types, and other filter options
- **BuildId management** — automatic extraction, PSR-16 caching, and retry on 404
- **Rate-limit friendly** — configurable delay between paginated requests via `searchAll()`
- **Fully typed DTOs** — all API responses mapped to strict `final readonly` classes
- **Test support** — `FakeAutoScout24` test double with factory classes for all DTOs
- **Quality** — 92 unit tests, PHPStan level 8 clean

#### Requirements

- PHP 8.4+
- PSR-16 cache implementation (optional, recommended)
