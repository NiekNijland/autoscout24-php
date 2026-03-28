# PHP Scraper/API Client Package Blueprint

Reusable architecture template derived from `nieknijland/viabovag-php` — a standalone PHP 8.4 Composer library that wraps a website's internal endpoints into a clean, typed PHP API with DTOs, caching, retry logic, and shipped test utilities.

---

## Architecture Overview (6 Layers)

```
src/
  {Package}Interface.php            # Public API contract
  {Package}.php                     # Main client — HTTP, session, caching, retry
  Parser/{Format}Parser.php         # Raw response → DTO mapping
  Data/                             # DTOs (readonly classes), enums, search criteria
    Concerns/                       # Shared traits
    Filters/                        # Filter value objects
  Exception/                        # Package exception hierarchy
  Testing/                          # Fake client + factories (shipped for consumers)
tests/
  {Package}Test.php                 # Client unit tests (mocked HTTP)
  {Format}ParserTest.php            # Parser unit tests
  SearchCriteriaTest.php            # Search criteria unit tests
  Fixtures/                         # HTML/JSON response fixtures
  Integration/                      # Live site tests (separate suite)
  Testing/                          # Tests for fake + factories
```

---

## Layer 1: Interface

A single interface defines the entire public API contract. Every consumer depends on this — never on the concrete client. This enables the fake to be swapped in seamlessly.

```php
interface {Package}Interface
{
    // Search for listings
    public function search(SearchQuery $query): SearchResult;

    // Auto-paginate through all pages, yielding each item lazily
    public function searchAll(SearchQuery $query): Generator;

    // Get available values for filter dimensions (brands, models, etc.)
    public function getBrands(CategoryType $type): array;
    public function getModels(CategoryType $type, ?Brand $brand = null): array;
    public function getFacetOptions(CategoryType $type, FacetName $facet, ...): array;

    // Get full detail for a single item
    public function getDetail(Listing $listing): ListingDetail;
    public function getDetailBySlug(string $slug, CategoryType $type): ListingDetail;
    public function getDetailByUrl(string $url): ListingDetail;

    // Force-refresh any session/auth tokens
    public function resetSession(): void;
}
```

**Methods fall into four categories:**

| Category | Purpose |
|---|---|
| Search | Query listings with filters, pagination |
| Facets/Filters | Metadata for building filter UIs (brands, models, options with counts) |
| Detail | Full item data by reference (DTO, slug, or URL) |
| Session | Force-refresh auth/session tokens |

---

## Layer 2: Client

The main class implementing the interface. Responsible for HTTP, session management, caching, and retry logic.

### Constructor

Accept a PSR-18 HTTP client and optional PSR-16 cache. Provide sensible defaults so zero-config usage works.

```php
public function __construct(
    private readonly ClientInterface $httpClient = new Client,
    private readonly ?CacheInterface $cache = null,
    private readonly int $cacheTtl = 3600,
)
```

### Session/Auth Token Management

Many sites require a token, build ID, or session value extracted from an initial page load before API calls can be made.

1. Check in-memory property first
2. Check PSR-16 cache second
3. If neither, fetch the source page and extract via the parser
4. Store in both in-memory and cache (with configurable TTL)

### Stale Token Retry Logic

When a request fails due to an expired/stale token (e.g. 404 on a Next.js `buildId`):

1. Save the previous token
2. Clear both in-memory and cache
3. Fetch a fresh token
4. If the new token equals the old one, the item is genuinely missing — re-throw
5. Retry the request once with the new token
6. A second failure is final

### HTTP Strategy

The client may need multiple HTTP strategies for different endpoints:

| Strategy | When | Example |
|---|---|---|
| REST API POST | Search, facets | `POST /api/search/results` with JSON body |
| Server-side data GET | Detail pages | `GET /_next/data/{buildId}/...` |
| HTML scrape | Token extraction | `GET /` to extract session values |

### Auto-Pagination

`searchAll()` is a Generator that calls `search()` in a loop:

```php
public function searchAll(SearchQuery $query): Generator
{
    $result = $this->search($query);
    yield from $result->listings;

    while ($result->hasNextPage()) {
        $query = $query->withPage($result->currentPage + 1);
        $result = $this->search($query);
        yield from $result->listings;
    }
}
```

### URL Parsing

`getDetailByUrl()` decomposes a full URL into slug + category type, then delegates to `getDetailBySlug()`.

---

## Layer 3: Parser

A stateless class that converts raw HTTP responses into DTOs. Separated from the client to keep HTTP and parsing concerns apart.

### Responsibilities

- Extract session tokens from HTML (regex or DOM parsing)
- Decode JSON and map nested structures to DTOs
- Handle source-specific quirks (locale-specific number formats, missing/null fields, mixed array formats, unit conversions)

### Common Parsing Patterns

| Pattern | Example |
|---|---|
| Structured value fields | `{value, formattedValue, hasValue}` → extract helpers |
| Locale-specific prices | `€ 19.850,-` → `19850` (int cents or whole units) |
| Unit extraction | `"83pk (61kW)"` → extract kW, or convert HP→kW |
| Fallback searches | Check primary field, fall back to searching specification groups |
| Mixed formats | Handle both string items (`"ABS"`) and object items (`{name: "ABS"}`) |

### Suggested Helper Methods

```php
private function extractFormattedValue(array $data, string $key): ?string;
private function extractNumericValue(array $data, string $key): ?int;
private function extractBoolValue(array $data, string $key): bool;
```

---

## Layer 4: Data

### DTOs

All DTOs are `readonly class` with promoted constructor properties. Pure data, no behavior (except trivial computed methods like stripping HTML from descriptions).

```php
readonly class Listing
{
    public function __construct(
        public string $id,
        public CategoryType $type,
        public string $url,
        public string $title,
        public int $price,
        public Vehicle $vehicle,
        public Company $company,
    ) {}
}
```

**Typical DTO hierarchy:**

| DTO | Purpose |
|---|---|
| `SearchResult` | Contains `Listing[]`, `totalCount`, `currentPage`, pagination helpers (`hasNextPage()`, `totalPages()`) |
| `Listing` | Search result item — id, title, price, nested objects |
| `ListingDetail` | Full detail — media, specifications, accessories, description |
| Domain objects | `Vehicle`, `Company`, `Media`, `Specification`, `SpecificationGroup`, etc. |
| Filter metadata | `Brand`, `Model`, `FilterOption` (slug, label, count) |
| Facets | `SearchFacet`, `SearchFacetOption`, `SearchFacetOptionCategory` |

### Enums

All enums are string-backed (or int-backed for numeric dimensions) with a `slug(): string` method for URL-friendly values.

```php
enum FuelType: string
{
    case Petrol = 'Benzine';
    case Diesel = 'Diesel';
    case Electric = 'Elektriciteit';

    public function slug(): string
    {
        return strtolower($this->value);
    }
}
```

Create one enum per filterable dimension: body type, fuel type, transmission, sort order, condition, distance, etc. Use `match` expressions for complex slug mappings.

### Search Criteria

Implements a `SearchQuery` interface with immutable pagination:

```php
interface SearchQuery
{
    public function toFilterSlugs(): array;   // URL-safe filter strings
    public function toRequestBody(): array;   // JSON body for REST API
    public function page(): int;
    public function withPage(int $page): static;
}
```

One concrete criteria class per category, sharing behavior via traits:

```php
readonly class CarSearchCriteria implements SearchQuery
{
    use HasSharedFilterSlugs;
    use HasSharedRequestBody;
    use HasWithPage;

    public function __construct(
        public ?Brand $brand = null,
        public ?int $priceFrom = null,
        public ?int $priceTo = null,
        // ... all nullable except page
        public int $page = 1,
    ) {}
}
```

**Traits:**

| Trait | Purpose |
|---|---|
| `HasSharedFilterSlugs` | Generates URL-friendly filter slugs from shared properties |
| `HasSharedRequestBody` | Generates JSON request body from shared properties |
| `HasWithPage` | Immutable `withPage()` via named arguments + spread |

**Filter Value Objects** — Group parameters for cleaner construction:

```php
$criteria = CarSearchCriteria::fromFilters(
    shared: new SharedSearchFilters(brand: $brand, priceFrom: 5000),
    filters: new CarSearchFilters(bodyTypes: [CarBodyType::Hatchback]),
    page: 2,
);
```

---

## Layer 5: Exceptions

Minimal hierarchy. All exceptions chain the original cause.

```
RuntimeException
  └── {Package}Exception       # Base — wraps HTTP, JSON, parsing errors
        └── NotFoundException  # 404 — triggers retry logic
```

```php
class ViaBOVAGException extends RuntimeException
{
    // Always chain: throw new self('msg', 0, $previous);
}

class NotFoundException extends ViaBOVAGException {}
```

**Throw for:**
- HTTP request failures (wrap `ClientExceptionInterface`)
- Non-200 status codes (set exception code to HTTP status)
- JSON decode failures (wrap `JsonException`)
- Missing expected data in responses
- Invalid URLs or unknown types
- Invalid pagination (page < 1)

---

## Layer 6: Testing Utilities

Shipped in `src/Testing/` (not `tests/`) so downstream consumers can use them in their own test suites.

### Fake Client

An in-memory implementation of the interface with fluent configuration and call recording.

```php
$fake = new Fake{Package};
$fake->withSearchResult(SearchResultFactory::make());
$fake->withListingDetail(ListingDetailFactory::make());

// Use in application code
$result = $fake->search($criteria);

// Assert in tests
$fake->assertCalled('search', times: 1);
$fake->assertNotCalled('getDetail');
```

**Features:**

| Feature | How |
|---|---|
| Fluent configuration | `withSearchResult()`, `withListingDetail()`, `shouldThrow()` |
| Call recording | Every method call stored as `RecordedCall(method, args)` |
| Assertions | `assertCalled()`, `assertNotCalled()`, `assertSessionReset()` |
| One-shot exceptions | Configured exception fires once, then clears |
| Sensible defaults | Returns factory-generated data if nothing configured |

### Factories

One factory per major DTO. All follow the same pattern:

```php
class ListingFactory
{
    public static function make(array $overrides = []): Listing
    {
        $defaults = [
            'id' => 'default-id',
            'title' => 'Default Listing',
            'price' => 15000,
            'vehicle' => VehicleFactory::make(),
            'company' => CompanyFactory::make(),
        ];

        $data = array_merge($defaults, $overrides);

        return new Listing(...$data);
    }

    /** @return Listing[] */
    public static function makeMany(int $count, array $overrides = []): array
    {
        // Generates unique IDs for each
    }
}
```

### RecordedCall DTO

```php
readonly class RecordedCall
{
    public function __construct(
        public string $method,
        public array $args,
    ) {}
}
```

---

## Testing Strategy

### Test Configuration (PHPUnit)

- Two suites: `Unit` (default, excludes Integration) and `Integration` (separate, hits live site)
- Strict mode: `failOnWarning`, `failOnRisky`, random execution order
- Bootstrap: `vendor/autoload.php`

### Test Layers

| Layer | Approach |
|---|---|
| Client tests | Guzzle `MockHandler` queues responses. `Middleware::history()` captures outgoing requests for URL, header, and body assertions. |
| Parser tests | Unit tests with fixture files from `tests/Fixtures/` (HTML, JSON). |
| Criteria tests | Pure unit tests on slug and request body generation. |
| Fake/Factory tests | Verify shipped test utilities work correctly. |
| Integration tests | Separate suite hitting the live site. Fetch data once in `setUpBeforeClass()`. |

### HTTP Mocking Pattern

```php
private function createClient(array $responses): {Package}
{
    $mock = new MockHandler($responses);
    $handler = HandlerStack::create($mock);
    $client = new Client(['handler' => $handler]);

    return new {Package}(httpClient: $client);
}

// With request history capture
private function createClientWithHistory(array $responses, array &$history): {Package}
{
    $mock = new MockHandler($responses);
    $handler = HandlerStack::create($mock);
    $handler->push(Middleware::history($history));
    $client = new Client(['handler' => $handler]);

    return new {Package}(httpClient: $client);
}
```

### Test Cache

A minimal in-memory PSR-16 implementation for testing cache behavior:

```php
class ArrayCache implements CacheInterface
{
    private array $store = [];
    // get, set, delete, clear, has, getMultiple, setMultiple, deleteMultiple
}
```

### Naming Conventions

- Method names: `test_` prefix with snake_case (`test_parses_search_results`)
- Prefer `assertSame()` (strict) over `assertEquals()`
- Use `expectException()` + `expectExceptionMessage()` for error cases
- Section separators: `// --- Build ID Extraction ---`

---

## Dependencies

| Dependency | Purpose | Required |
|---|---|---|
| `guzzlehttp/guzzle` | HTTP client (PSR-18) | Runtime |
| `psr/simple-cache` | Caching interface (PSR-16) | Runtime |
| `phpunit/phpunit` | Testing | Dev |
| `phpstan/phpstan` | Static analysis (level 8) | Dev |
| `laravel/pint` | Code formatting | Dev |
| `rector/rector` | Automated refactoring | Dev |

---

## Composer Scripts

```json
{
    "scripts": {
        "test": "phpunit --testsuite Unit --no-coverage",
        "test-coverage": "phpunit --testsuite Unit --coverage-clover ...",
        "test-integration": "phpunit --testsuite Integration --no-coverage",
        "format": "pint",
        "analyse": "phpstan analyse",
        "codestyle": ["@rector", "@format", "@analyse"]
    }
}
```

---

## Code Style Summary

- PHP ^8.4, `declare(strict_types=1)` in every file
- `readonly class` for all DTOs, `private readonly` promoted properties on services
- Native types everywhere — PHPDoc only for array shapes PHP cannot express
- String-backed enums with `slug()` methods
- PascalCase classes, camelCase methods/properties, `UPPER_SNAKE` constants
- `Has*` trait naming in `Concerns/` directory
- `*Interface` suffix on interfaces
- `is*`/`has*` prefix on boolean properties
- Fully qualified imports, alphabetically ordered
- Custom exception hierarchy, always chain previous exceptions
- No mocking frameworks — Guzzle MockHandler only

---

## Checklist: Replicating for Another Source

1. **Define the interface** — What can consumers do? Search, detail, list filters?
2. **Build DTOs** — Model the source's data as `readonly class` objects
3. **Build enums** — One per filterable dimension, with `slug()` methods
4. **Build search criteria** — Immutable query objects with `withPage()`, use traits for shared logic
5. **Build the parser** — Stateless class mapping raw responses to DTOs, isolate all source quirks here
6. **Build the client** — HTTP + session/token management + retry logic, accept PSR-18 + PSR-16
7. **Build exceptions** — Base + NotFoundException (or equivalent for retry trigger)
8. **Build testing utilities** — Fake implementation + factories for every major DTO, ship in `src/Testing/`
9. **Write tests** — Mock HTTP at Guzzle layer, store fixtures as files, separate integration suite
10. **Configure CI** — PHPUnit, PHPStan, Pint, Rector
