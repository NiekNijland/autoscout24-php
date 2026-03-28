# AutoScout24 PHP Package — Implementation Plan

## Target

Standalone PHP 8.4 Composer package (`nieknijland/autoscout24`) that wraps AutoScout24.nl's internal Next.js data endpoints into a clean, typed PHP API. Supports cars and motorcycles. Consumed by the MotorMonitor Laravel app.

> **Note:** Campers (`lst-wohnmobile`) are not available on the `.nl` domain (returns 404). Camper support can be added if the package targets `.de` in the future.

Follows the architecture defined in `BLUEPRINT.md` and the shared conventions in the workspace `AGENTS.md`.

---

## API Findings

### Technology

AutoScout24.nl is a **Next.js** application. Every page embeds its data in a `<script id="__NEXT_DATA__">` JSON blob. The same data is available via `_next/data/{buildId}/{pagePath}.json` endpoints, which return pure JSON.

Despite the site using `assetPrefix: "/assets/as24-search-funnel"` for static assets, the `_next/data` endpoints do **not** use this prefix. The `locale` is `null` — no `/nl/` segment in the data path. The correct base is simply `/_next/data/{buildId}/...`.

### Build ID

The `buildId` is required for all `_next/data` requests. It follows the pattern:

```
as24-search-funnel_main-{YYYYMMDDHHMMSS}
```

Extracted from HTML via regex:

```
/"buildId":"([^"]+)"/
```

The `buildId` can be extracted from any page that uses the search funnel Next.js app (e.g. `/lst`, `/lst-moto`, or any `/aanbod/{slug}` page). The homepage (`/`) also embeds it because it includes the search funnel component, but the homepage itself is a separate app — so this is a known fragility if AutoScout24 changes their homepage architecture.

The buildId rotates on deploys. A stale buildId returns 404, requiring the retry-once pattern from the blueprint.

### Endpoints

| Purpose | Method | URL |
|---------|--------|-----|
| Build ID extraction | GET | `https://www.autoscout24.nl/lst` (HTML, parse `__NEXT_DATA__`) |
| Search (all types) | GET | `/_next/data/{buildId}/lst.json?{params}` |
| Detail page | GET | `/_next/data/{buildId}/details/{slug}.json` |

All `_next/data` requests require the header `x-nextjs-data: 1`.

**Search path:** All vehicle types use the same `lst.json` endpoint. The `atype` query parameter controls the vehicle type: absent or `C` = cars, `B` = motorcycles.

**Detail path:** The user-facing URL pattern is `/aanbod/{slug}`, but the Next.js internal page route is `/details/[...slug]`. The `_next/data` endpoint uses the internal route: `/_next/data/{buildId}/details/{slug}.json` (not `/aanbod/`).

> **Note:** The user-facing paths `/lst-moto`, `/lst-wohnmobile` are server-side rewrites to `/lst?atype=B`, `/lst?atype=M` etc. The `_next/data` endpoint does not support these rewritten paths — only `/lst.json` works.

### Search Query Parameters

From the observed `pageQuery` structure:

| Parameter | Example | Purpose |
|-----------|---------|---------|
| `mmmv` | `68\|77700\|\|` | Make ID, Model ID, Model Line ID, Variant ID (pipe-separated) |
| `sort` | `standard` | Sort field: `standard`, `price`, `age`, `mileage`, `power` |
| `desc` | `0` | Sort direction: `0` = ascending, `1` = descending |
| `ustate` | `N,U` | Offer types: `N` = New, `U` = Used, `D` = Day registration, `O` = Oldtimer, `J` = Young used, `S` = Semi-new |
| `atype` | `B` | Article type: `B` = Motorbike, absent or `C` = Car (default). Controls vehicle type for the unified `/lst.json` endpoint. |
| `cy` | `NL` | Country code |
| `powertype` | `kw` | Power unit: `kw` or `hp` |
| `pricefrom` | `5000` | Min price |
| `priceto` | `20000` | Max price |
| `fregfrom` | `2020` | First registration year from |
| `fregto` | `2025` | First registration year to |
| `kmfrom` | `0` | Mileage from |
| `kmto` | `50000` | Mileage to |
| `powerfrom` | `50` | Power from (in unit specified by `powertype`) |
| `powerto` | `200` | Power to |
| `body` | `101,102` | Body type IDs (comma-separated) |
| `fuel` | `B,D` | Fuel type codes |
| `gear` | `A,M` | Transmission codes: `A` = Automatic, `M` = Manual, `S` = Semi-auto |
| `bcol` | `1,2` | Body color IDs (comma-separated) |
| `page` | `2` | Page number (1-based) |
| `damaged_listing` | `exclude` | Exclude damaged listings |
| `pricetype` | `public` | Price visibility |
| `custtype` | `D` | Customer/seller type: `D` = Dealer, `P` = Private |
| `eq` | `161,37` | Equipment/condition IDs (comma-separated) |
| `zip` | `1234AB` | Postal code for distance search |
| `zipr` | `100` | Radius in km |
| `offer` | `D` | Online since filter |

### Search Response Structure (`pageProps`)

```
pageProps:
  numberOfResults: int              — total matching listings
  numberOfPages: int                — total pages
  pagePrefix: string                — always "lst" from _next/data (user-facing pages show "lst-moto" etc.)
  listings: Listing[]               — array of listing objects (see below)
  taxonomy:                         — filter metadata
    makes: {id: {label, value}}     — all brands (447 for motorcycles)
    makesSorted: [{label, value}]   — alphabetically sorted
    topMakeIds: [int]               — top 10 brand IDs
    topMakes: [{label, value}]      — top 10 brands
    otherMakes: [{label, value}]    — remaining brands
    models: {makeId: [{value, label, makeId, modelLineId}]}  — models per brand
    bodyType: [{label, value}]      — body types (21 for moto)
    fuelType: [{label, value}]      — fuel types
    gearing: [{label, value}]       — transmission types
    cylinders: [{label, value}]     — cylinder counts
    drivetrain: [{label, value}]    — drive types
    offer: [{label, value}]         — condition/offer types
    sortingKeys: [{label, value}]   — sort options
    bodyColor: [{label, value}]     — colors (14)
    bodyPainting: [{label, value}]  — paint finishes
    equipment: [{label, value}]     — equipment filters (26 for moto)
    radius: [int]                   — distance radius options
    country: [{label, value, countryCode}]
    priceFrom/priceTo: [{label, value}]
    mileageFrom/mileageTo: [{label, value}]
    firstRegistrationFrom/To: [int]
    onlineSince: [{label, value}]
    customerType: [{label, value}]  — seller types
    seals: [{label, value, makes, culture, info}]  — dealer seals/certifications
    priceType: [{label, value}]
    emissionClass: [{label, value}]
    powerType: [{label, value}]     — kw/hp
    batteryOwnershipType: [{label, value}]
    electricRangeFrom/To: [int]
    seatsFrom/To, doorsFrom/To: [{label, value}] or [int]
    leasingRateFrom/To, leasingDurationFrom/To: [{label, value}]
    financeRateFrom/To: [{label, value}]
  pageQuery: {...}                  — the query params that produced this result
  breadcrumbs: [{...}]
  adTargetingString: string
```

### Listing Object (Search Result)

```json
{
  "id": "b26efa31-229e-4c7b-bdc9-cfb74a3e7467",         // UUID
  "identifier": {
    "legacyId": null,
    "crossReferenceId": "321956762"
  },
  "crossReferenceId": "321956762",
  "images": ["https://prod.pictures.autoscout24.net/listing-images/{id}_{hash}.jpg/250x188.webp"],
  "price": {
    "priceFormatted": "€ 9.999",
    "vatLabel": "Incl. BTW",
    "isVatLabelLegallyRequired": false,
    "priceSuperscriptString": "1",
    "isConditionalPrice": false
  },
  "availableNow": true,
  "superDeal": {
    "oldPriceFormatted": "",
    "isEligible": false
  },
  "url": "/aanbod/suzuki-dr-z4sm-zwart-b26efa31-229e-4c7b-bdc9-cfb74a3e7467",
  "vehicle": {
    "articleType": "Motorbike",       // "Motorbike" or "Car"
    "type": "Motorbike",
    "make": "Suzuki",
    "model": "DR-Z4SM",
    "modelGroup": null,
    "variant": null,
    "modelId": 77700,
    "modelVersionInput": null,
    "subtitle": null,
    "offerType": "N",                 // N=New, U=Used, D=DayRegistration
    "mileageInKm": "0 km"
  },
  "location": {
    "countryCode": "NL",
    "zip": "2411 NE",
    "city": "BODEGRAVEN",
    "street": "Europaweg 1D"
  },
  "seller": {
    "dealer": {},
    "id": "24101423",
    "type": "Dealer",                 // "Dealer" or "Private"
    "companyName": "Goedhart Motoren B.V",
    "contactName": "Afdeling Verkoop",
    "links": {
      "infoPage": "https://www.autoscout24.nl/autobedrijven/...",
      "imprint": "https://www.autoscout24.nl/autobedrijven/.../impressum"
    },
    "phones": [
      {
        "phoneType": "Office",
        "formattedNumber": "+31 (0)172 - 650005",
        "callTo": "+31172650005"
      }
    ]
  },
  "vehicleDetails": [
    {"data": "0 km", "iconName": "mileage_odometer", "ariaLabel": "Kilometerstand"},
    {"data": "- Transmissie", "isPlaceholder": true, "iconName": "gearbox", "ariaLabel": "Transmissie"},
    {"data": "01/2026", "iconName": "calendar", "ariaLabel": "Bouwjaar"},
    {"data": "- Brandstof", "isPlaceholder": true, "iconName": "gas_pump", "ariaLabel": "Brandstof"},
    {"data": "28 kW (38 PK)", "iconName": "speedometer", "ariaLabel": "Vermogen kW (PK)"}
  ],
  "tracking": {
    "firstRegistration": "01-2026",
    "fuelType": "unknown",
    "mileage": "0",
    "price": "9999"
  },
  "appliedAdTier": "T10",
  "adTier": "T10",
  "isOcs": false,
  "specialConditions": [],
  "searchResultType": "Organic",
  "searchResultSection": "Main",
  "trackingParameters": [
    {"key": "boost_level", "value": "t10"},
    {"key": "applied_boost_level", "value": "t10"}
  ]
}
```

### Detail Page Response (`pageProps.listingDetails`)

```json
{
  "id": "b26efa31-229e-4c7b-bdc9-cfb74a3e7467",
  "searchResultType": "Organic",
  "isDeliverable": false,
  "description": "HTML description with <br /> tags and <strong> formatting",
  "ratings": null,
  "images": ["https://prod.pictures.autoscout24.net/listing-images/{id}_{hash}.jpg/1280x960.webp"],
  "headerImage": "...120x90.webp",
  "youtubeLink": null,
  "twinnerUrl": null,

  "prices": {
    "isFinalPrice": false,
    "public": {
      "price": "€ 9.999",
      "priceRaw": 9999,
      "taxDeductible": true,
      "negotiable": false,
      "onRequestOnly": false,
      "netPrice": null,
      "netPriceRaw": null,
      "vatRate": null
    },
    "dealer": { "...same structure..." },
    "suggestedRetail": null
  },
  "price": {
    "priceFormatted": "€ 9.999",
    "vatLabel": "Incl. BTW",
    "isVatLabelLegallyRequired": false,
    "priceSuperscriptString": "1",
    "isConditionalPrice": false
  },

  "vehicle": {
    "makeId": 68,
    "modelOrModelLineId": 77700,
    "make": "Suzuki",
    "model": "DR-Z4SM",
    "modelGroup": null,
    "variant": null,
    "modelId": 77700,
    "type": "Motorbike",
    "mileageInKmRaw": 0,
    "mileageInKm": "0 km",
    "firstRegistrationDateRaw": "2026-01-01",
    "firstRegistrationDate": "01/2026",
    "bodyType": "Supermotard",
    "bodyColor": "Zwart",
    "bodyColorOriginal": "Solid Special W",
    "paintType": null,
    "numberOfSeats": null,
    "numberOfDoors": null,
    "powerInKw": "28 kW",
    "powerInHp": "38 PK",
    "rawPowerInKw": 28,
    "rawPowerInHp": 38,
    "transmissionType": null,
    "gears": null,
    "cylinders": null,
    "driveTrain": null,
    "displacementInCCM": null,
    "rawDisplacementInCCM": null,
    "cylinderCapacity": null,
    "rawCylinderCapacity": null,
    "weight": null,
    "upholstery": null,
    "upholsteryColor": null,
    "licensePlate": null,
    "fuelCategory": {"raw": null, "formatted": null},
    "primaryFuel": {"raw": null, "formatted": null},
    "additionalFuel": [],
    "fuelConsumptionCombined": {"raw": null, "formatted": null, "isFallback": null},
    "batteryOwnershipType": {"raw": null, "formatted": null},
    "batteryChargingTime": {"raw": null, "formatted": null},
    "electricRangeWithFallback": {"raw": null, "formatted": null},
    "hasParticleFilter": false,
    "co2EmissionCombinedFormatted": null,
    "emissionClass": null,
    "emissionSticker": null
  },

  "seller": {
    "id": "24101423",
    "type": "Dealer",
    "companyName": "Goedhart Motoren B.V",
    "contactName": "Afdeling Verkoop",
    "address": {
      "zip": "2411 NE",
      "city": "BODEGRAVEN",
      "countryCode": "NL",
      "street": "Europaweg 1D"
    },
    "phones": [...],
    "links": {...},
    "reviewRating": null,
    "reviewCount": null,
    "seal": null
  },

  "location": {
    "countryCode": "NL",
    "zip": "2411 NE",
    "city": "BODEGRAVEN",
    "street": "Europaweg 1D",
    "latitude": 52.0833,
    "longitude": 4.7500
  },

  "availability": {"rawFromDate": null, "fromDate": null, "inDays": null},
  "specialConditions": [],
  "hasThreeSixtyContent": false,
  "coverImageAttractiveness": 0.72
}
```

---

## Architecture

### Directory Structure

```
src/
  AutoScout24Interface.php
  AutoScout24.php
  Parser/
    JsonParser.php
  Data/
    SearchQuery.php                    # Interface
    SearchResult.php
    Listing.php
    ListingDetail.php
    Vehicle.php
    DetailVehicle.php
    Seller.php
    SellerAddress.php
    SellerLinks.php
    Location.php
    Phone.php
    Price.php
    DetailPrices.php
    DetailPriceInfo.php
    SuperDeal.php
    VehicleDetail.php
    VehicleFuelInfo.php
    Brand.php
    Model.php
    FilterOption.php
    VehicleType.php                    # Enum
    SortOrder.php                      # Enum
    OfferType.php                      # Enum
    SellerType.php                     # Enum
    PowerType.php                      # Enum
    Concerns/
      HasSharedQueryParams.php
      HasWithPage.php
    Filters/
      SharedSearchFilters.php
      CarSearchFilters.php
      MotorcycleSearchFilters.php
    CarSearchCriteria.php
    MotorcycleSearchCriteria.php
  Exception/
    AutoScout24Exception.php
    NotFoundException.php
  Testing/
    FakeAutoScout24.php
    RecordedCall.php
    SearchResultFactory.php
    ListingFactory.php
    ListingDetailFactory.php
    VehicleFactory.php
    DetailVehicleFactory.php
    SellerFactory.php
    LocationFactory.php
    BrandFactory.php
    ModelFactory.php
tests/
  AutoScout24Test.php
  JsonParserTest.php
  SearchCriteriaTest.php
  ArrayCache.php
  Fixtures/
    search-page.html
    search-results.json
    detail.json
  Testing/
    FakeAutoScout24Test.php
    FactoryTest.php
  Integration/
    IntegrationTest.php
```

### Layer 1: Interface

```php
interface AutoScout24Interface
{
    /** Search for vehicle listings. */
    public function search(SearchQuery $query): SearchResult;

    /** Auto-paginate through all pages, yielding each Listing lazily. */
    public function searchAll(SearchQuery $query): Generator;

    /** Get available brands for a vehicle type. */
    public function getBrands(VehicleType $type): array;

    /** Get available models for a brand within a vehicle type. */
    public function getModels(VehicleType $type, Brand $brand): array;

    /** Get available filter options from taxonomy (body types, fuel types, etc.). */
    public function getFilterOptions(VehicleType $type, string $taxonomyKey): array;

    /** Get full detail for a listing. */
    public function getDetail(Listing $listing): ListingDetail;

    /** Get full detail by URL slug. */
    public function getDetailBySlug(string $slug): ListingDetail;

    /** Get full detail by full URL. */
    public function getDetailByUrl(string $url): ListingDetail;

    /** Force-refresh the buildId. */
    public function resetSession(): void;
}
```

`getBrands()` returns `Brand[]`. `getModels()` returns `Model[]`. `getFilterOptions()` returns `FilterOption[]`. No untyped arrays with implicit structure.

### Layer 2: Client

**Constructor:**

```php
public function __construct(
    private readonly ClientInterface $httpClient = new Client(),
    private readonly ?CacheInterface $cache = null,
    private readonly int $cacheTtl = 3600,
)
```

**Constants:**

```php
private const string BASE_URL = 'https://www.autoscout24.nl';
private const string CACHE_KEY = 'autoscout24:build-id';
```

**Build ID lifecycle:**

1. Check `$this->buildId` (in-memory)
2. Check PSR-16 cache
3. Fetch `GET /lst` HTML (a search funnel page that reliably contains the `__NEXT_DATA__` script)
4. Extract via `JsonParser::extractBuildId()`
5. Store in both memory and cache with TTL

**Stale build ID retry:**

1. On `NotFoundException` (404 from `_next/data`):
2. Save previous build ID
3. Clear memory + cache
4. Fetch fresh build ID
5. If new === old, re-throw (genuinely missing)
6. Retry request once with new build ID
7. Second failure is final

**HTTP strategy:**

All data requests go through `_next/data`:

```
GET {BASE_URL}/_next/data/{buildId}/{path}.json?{queryString}
Headers:
  x-nextjs-data: 1
  User-Agent: Mozilla/5.0 ...
```

**Search path construction:**

All vehicle types use the same path. The `atype` query parameter differentiates:

```php
// Car search:
// /_next/data/{id}/lst.json?sort=standard&cy=NL&ustate=N,U

// Motorcycle search:
// /_next/data/{id}/lst.json?sort=standard&cy=NL&ustate=N,U&atype=B
```

The `VehicleType::articleTypeParam()` method returns the `atype` value (`null` for Car, `"B"` for Motorcycle). The client appends it to the query string only when non-null.

**Detail path construction:**

```php
// /_next/data/{id}/details/{slug}.json
```

The slug is extracted from the user-facing `/aanbod/{slug}` URL. The `_next/data` endpoint uses the internal route `/details/{slug}`, not `/aanbod/{slug}`.

**Auto-pagination (`searchAll`):**

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

> **Warning:** No built-in rate limiting. Broad queries can have hundreds of pages. Callers are responsible for throttling or limiting page depth.

**URL parsing (`getDetailByUrl`):**

Parses `/aanbod/{slug}` from a full URL, extracts the slug, and delegates to `getDetailBySlug()`. The client internally converts the slug to the `/details/{slug}` path for the `_next/data` request.

### Layer 3: Parser

Stateless `JsonParser` class. Converts raw JSON arrays (decoded from HTTP responses) into typed DTOs.

**Public methods:**

| Method | Input | Output |
|--------|-------|--------|
| `extractBuildId(string $html)` | Any page HTML containing `__NEXT_DATA__` (e.g. `/lst`) | `string` |
| `parseSearchResults(array $data, int $currentPage)` | Decoded `_next/data` JSON, page number | `SearchResult` |
| `parseDetail(array $data)` | Decoded `_next/data` JSON | `ListingDetail` |
| `parseBrands(array $data)` | Decoded `_next/data` JSON | `Brand[]` |
| `parseModels(array $data, Brand $brand)` | Decoded `_next/data` JSON, brand | `Model[]` |
| `parseFilterOptions(array $data, string $taxonomyKey)` | Decoded `_next/data` JSON, key | `FilterOption[]` |

**Key parsing details:**

- Build ID regex: `/"buildId":"([^"]+)"/`
- Prices: use `priceRaw` (int) from detail, parse formatted price from search listings
- Power: `rawPowerInKw` and `rawPowerInHp` are raw ints on detail; parsed from `vehicleDetails` on search
- Mileage: `mileageInKmRaw` on detail; parsed from `vehicleDetails[0].data` on search
- Registration: `firstRegistrationDateRaw` is ISO date string `"2026-01-01"`
- Description: contains HTML (`<br />`, `<strong>`). `ListingDetail` has `descriptionText()` that strips tags
- Images: full CDN URLs with resolution suffixes (e.g. `/1280x960.webp`)
- Taxonomy data: `pageProps.taxonomy` contains makes, models, bodyType, fuelType, gearing, etc. as `{label, value}` pairs

### Layer 4: Data

**DTOs** — all `readonly class` with promoted constructor properties. Include `toArray()` and `static fromArray()` methods.

| DTO | Key properties | Notes |
|-----|---------------|-------|
| `SearchResult` | `listings: Listing[]`, `totalCount: int`, `currentPage: int`, `numberOfPages: int` | Has `hasNextPage()`, `pageSize()` |
| `Listing` | `id: string`, `url: string`, `images: string[]`, `price: Price`, `availableNow: bool`, `superDeal: SuperDeal`, `vehicle: Vehicle`, `location: Location`, `seller: Seller`, `vehicleDetails: VehicleDetail[]` | Search result item |
| `ListingDetail` | `id: string`, `description: ?string`, `images: string[]`, `prices: DetailPrices`, `price: Price`, `vehicle: DetailVehicle`, `seller: Seller`, `location: Location`, `url: ?string` | Has `descriptionText()` to strip HTML |
| `Vehicle` | `articleType: string`, `type: string`, `make: string`, `model: string`, `modelId: int`, `offerType: string`, `mileageInKm: string`, `?modelGroup`, `?variant`, `?subtitle` | Search-level vehicle info |
| `DetailVehicle` | All of `Vehicle` fields plus: `makeId: int`, `bodyType: ?string`, `bodyColor: ?string`, `mileageInKmRaw: int`, `firstRegistrationDateRaw: ?string`, `firstRegistrationDate: ?string`, `rawPowerInKw: ?int`, `rawPowerInHp: ?int`, `?transmissionType`, `?gears`, `?cylinders`, `?driveTrain`, `?rawDisplacementInCCM`, `?weight`, `?licensePlate`, `?numberOfSeats`, `?numberOfDoors`, `fuelCategory: VehicleFuelInfo`, `primaryFuel: VehicleFuelInfo`, `?emissionClass`, `?co2EmissionCombinedFormatted` | Full detail vehicle |
| `VehicleFuelInfo` | `raw: ?string`, `formatted: ?string` | Reused for fuelCategory, primaryFuel, batteryOwnershipType, etc. |
| `Seller` | `id: string`, `type: string`, `companyName: ?string`, `contactName: ?string`, `phones: Phone[]`, `links: SellerLinks`, `?address: SellerAddress`, `?reviewRating: ?float`, `?reviewCount: ?int` | |
| `SellerAddress` | `zip: ?string`, `city: ?string`, `countryCode: ?string`, `street: ?string` | Only on detail |
| `SellerLinks` | `infoPage: ?string`, `imprint: ?string` | |
| `Location` | `countryCode: string`, `zip: ?string`, `city: ?string`, `street: ?string`, `?latitude: ?float`, `?longitude: ?float` | Coordinates only on detail |
| `Phone` | `phoneType: string`, `formattedNumber: string`, `callTo: string` | |
| `Price` | `priceFormatted: string`, `vatLabel: ?string`, `isVatLabelLegallyRequired: bool`, `isConditionalPrice: bool` | On both search and detail |
| `DetailPrices` | `isFinalPrice: bool`, `public: DetailPriceInfo`, `dealer: DetailPriceInfo`, `suggestedRetail: ?DetailPriceInfo` | Detail-only pricing |
| `DetailPriceInfo` | `price: string`, `priceRaw: int`, `taxDeductible: bool`, `negotiable: bool`, `onRequestOnly: bool`, `netPrice: ?string`, `netPriceRaw: ?int`, `vatRate: ?string` | |
| `SuperDeal` | `oldPriceFormatted: ?string`, `isEligible: bool` | |
| `VehicleDetail` | `data: string`, `iconName: string`, `ariaLabel: string`, `isPlaceholder: bool` | Quick-spec chips on search results |
| `Brand` | `value: int`, `label: string` | Filter metadata |
| `Model` | `value: int`, `label: string`, `makeId: int`, `?modelLineId: ?int` | Filter metadata |
| `FilterOption` | `value: int\|string`, `label: string` | Generic filter value |

**Enums:**

| Enum | Backing | Cases | Notes |
|------|---------|-------|-------|
| `VehicleType` | `string` | `Car("Car")`, `Motorcycle("Motorbike")` | Backing values match the `articleType` field in API responses. `articleTypeParam(): ?string` returns `null` for Car (API defaults to `C`), `"B"` for Motorcycle. |
| `SortOrder` | `string` | `Standard("standard")`, `PriceAscending("price_asc")`, `PriceDescending("price_desc")`, `YearAscending("age_asc")`, `YearDescending("age_desc")`, `MileageAscending("mileage_asc")`, `MileageDescending("mileage_desc")`, `PowerAscending("power_asc")`, `PowerDescending("power_desc")` | Internal values (not API values). `sortField(): string` and `isDescending(): bool` split into the `sort` and `desc` API params. |
| `OfferType` | `string` | `New("N")`, `Used("U")`, `DayRegistration("D")`, `Oldtimer("O")`, `YoungUsed("J")`, `SemiNew("S")` | Maps to `ustate` param values |
| `SellerType` | `string` | `Dealer("D")`, `Private("P")` | Maps to `custtype` query param. Note: API responses use full words (`"Dealer"`, `"Private"`) in `seller.type` — the parser maps those, not this enum. |
| `PowerType` | `string` | `Kw("kw")`, `Hp("hp")` | Maps to `powertype` query param. Also in taxonomy as `powerType`. |

Body types, fuel types, colors, etc. are represented as `FilterOption` (value + label from taxonomy) rather than hardcoded enums, since AutoScout24's taxonomy values differ per vehicle type and may change.

**Search Criteria:**

Interface:

```php
interface SearchQuery
{
    public function vehicleType(): VehicleType;
    public function toQueryParams(): QueryParams;
    public function page(): int;
    public function withPage(int $page): static;
}
```

`toQueryParams()` returns a `QueryParams` DTO (not an array). `QueryParams` is a readonly class holding the structured parameters that the client converts to a URL query string.

```php
readonly class QueryParams
{
    public function __construct(
        public ?string $mmmv = null,
        public string $sort = 'standard',
        public string $desc = '0',
        public ?string $ustate = null,
        public ?string $atype = null,
        public string $cy = 'NL',
        public ?int $pricefrom = null,
        public ?int $priceto = null,
        public ?int $fregfrom = null,
        public ?int $fregto = null,
        public ?int $kmfrom = null,
        public ?int $kmto = null,
        public ?int $powerfrom = null,
        public ?int $powerto = null,
        public PowerType $powertype = PowerType::Kw,
        public ?string $body = null,
        public ?string $fuel = null,
        public ?string $gear = null,
        public ?string $bcol = null,
        public ?string $custtype = null,
        public ?string $eq = null,
        public ?string $zip = null,
        public ?int $zipr = null,
        public ?int $page = null,
        public ?string $offer = null,
        public bool $excludeDamaged = false,
        public ?string $pricetype = null,
    ) {}

    /** Convert to URL query string array, omitting null values. Maps excludeDamaged to damaged_listing=exclude, PowerType to its string value. */
    public function toArray(): array;
}
```

Per-type criteria classes share behavior via traits:

| Trait | Purpose |
|-------|---------|
| `HasSharedQueryParams` | Builds QueryParams from shared filter properties (brand, price, year, mileage, power, etc.) |
| `HasWithPage` | Immutable `withPage(int): static` via named args + spread |

Each criteria class has a `fromFilters()` static factory:

```php
MotorcycleSearchCriteria::fromFilters(
    shared: new SharedSearchFilters(brand: $brand, priceFrom: 5000),
    filters: new MotorcycleSearchFilters(bodyTypes: [$supermotard]),
    page: 2,
);
```

### Layer 5: Exceptions

```php
class AutoScout24Exception extends RuntimeException
{
    // Always chain: throw new self('msg', 0, $previous);
}

class NotFoundException extends AutoScout24Exception {}
```

Thrown for: HTTP failures (wrap `ClientExceptionInterface`), non-200 status codes, JSON decode failures, missing expected data, invalid URLs, page < 1.

### Layer 6: Testing Utilities

**FakeAutoScout24** — In-memory implementation of `AutoScout24Interface`:

- Fluent config: `withSearchResult()`, `withListingDetail()`, `withBrands()`, `withModels()`, `shouldThrow()`
- Call recording via `RecordedCall` (method + args)
- Assertions: `assertCalled()`, `assertNotCalled()`, `assertSessionReset()`
- One-shot exceptions (fire once, then clear)
- Sensible factory defaults when nothing configured

**Factories** — One per major DTO, all with `static make(array $overrides = []): T` and `static makeMany(int $count, array $overrides = []): T[]`:

- `SearchResultFactory`, `ListingFactory`, `ListingDetailFactory`
- `VehicleFactory`, `DetailVehicleFactory`
- `SellerFactory`, `LocationFactory`
- `BrandFactory`, `ModelFactory`

---

## Testing Strategy

### Test Suites

- **Unit** (default): `tests/` excluding `tests/Integration/`
- **Integration**: `tests/Integration/` only, hits live AutoScout24

### Test Layers

| Layer | Approach |
|-------|----------|
| Client tests (`AutoScout24Test`) | Guzzle `MockHandler` + `Middleware::history()`. Test buildId extraction, search, detail, stale retry logic, pagination, error handling. |
| Parser tests (`JsonParserTest`) | Unit tests with fixture files from `tests/Fixtures/`. |
| Criteria tests (`SearchCriteriaTest`) | Pure tests on `toQueryParams()` output, `withPage()`, validation. |
| Fake/Factory tests | Verify shipped test utilities work correctly. |
| Integration tests | Live site. Fetch data once in `setUpBeforeClass()`. |

### Conventions (from workspace AGENTS.md)

- Every PHP file must have `declare(strict_types=1)`
- Use `assertSame()` over `assertEquals()` in all tests
- PHPUnit with `test_` prefix and snake_case method names
- PHPStan level 8
- Fully qualified imports, native types on all parameters/return types

### HTTP Mocking Pattern

```php
private function createClient(array $responses): AutoScout24
{
    $mock = new MockHandler($responses);
    $handler = HandlerStack::create($mock);
    $client = new Client(['handler' => $handler]);
    return new AutoScout24(httpClient: $client);
}

private function createClientWithHistory(array $responses, array &$history): AutoScout24
{
    $mock = new MockHandler($responses);
    $handler = HandlerStack::create($mock);
    $handler->push(Middleware::history($history));
    $client = new Client(['handler' => $handler]);
    return new AutoScout24(httpClient: $client);
}
```

---

## Project Setup Changes

### composer.json updates

Add runtime dependencies:
- `guzzlehttp/guzzle: ^7.0`
- `psr/simple-cache: ^3.0`

Add dev dependencies:
- `phpstan/phpstan: ^2.0`
- `rector/rector: ^2.0`

Add composer scripts:
- `test-integration`
- `test-all`
- `analyse`
- `rector`
- `codestyle` (rector + format + analyse)

### phpunit.xml.dist updates

Split into two suites:
- `Unit`: `tests/` excluding `tests/Integration/`
- `Integration`: `tests/Integration/`

### Cleanup

- Remove `src/AutoScout24Class.php` (empty placeholder)
- Remove `tests/ExampleTest.php` (empty placeholder)

---

## Implementation Order

| Step | What | Depends on |
|------|------|------------|
| 1 | Project setup (composer.json, phpunit.xml.dist, cleanup placeholders) | — |
| 2 | Exceptions (`AutoScout24Exception`, `NotFoundException`) | — |
| 3 | Enums (`VehicleType`, `SortOrder`, `OfferType`, `SellerType`, `PowerType`) | — |
| 4 | Core DTOs (`Brand`, `Model`, `FilterOption`, `Phone`, `SellerLinks`, `SellerAddress`, `Seller`, `Location`, `VehicleFuelInfo`, `Price`, `SuperDeal`, `VehicleDetail`) | Enums |
| 5 | Search DTOs (`Vehicle`, `Listing`, `SearchResult`, `SearchQuery` interface) | Core DTOs |
| 6 | Detail DTOs (`DetailVehicle`, `DetailPriceInfo`, `DetailPrices`, `ListingDetail`) | Core DTOs |
| 7 | Query params (`QueryParams` DTO) | — |
| 8 | Search criteria (traits, filter DTOs, per-type criteria classes) | Enums, DTOs, QueryParams |
| 9 | Parser (`JsonParser`) | All DTOs |
| 10 | Client (`AutoScout24Interface` + `AutoScout24`) | Parser, Criteria, Exceptions |
| 11 | Capture real fixtures (search page HTML, search JSON, detail JSON) | Client working |
| 12 | Testing utilities (Fake, Factories, RecordedCall) | DTOs |
| 13 | Unit tests (client, parser, criteria, fake/factory) | All above |
| 14 | Integration tests | Client |
| 15 | Static analysis config + passing (PHPStan level 8, Rector, Pint) | All code |

Steps 2, 3, 7 are independent and can be done in parallel. Steps 4, 5, 6 are semi-parallel.
