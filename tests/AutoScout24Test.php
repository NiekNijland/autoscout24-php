<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use NiekNijland\AutoScout24\AutoScout24;
use NiekNijland\AutoScout24\Data\CarSearchCriteria;
use NiekNijland\AutoScout24\Data\MotorcycleSearchCriteria;
use NiekNijland\AutoScout24\Data\VehicleType;
use NiekNijland\AutoScout24\Exception\AutoScout24Exception;
use NiekNijland\AutoScout24\Exception\NotFoundException;
use NiekNijland\AutoScout24\Parser\JsonParser;
use NiekNijland\AutoScout24\Testing\ListingFactory;
use PHPUnit\Framework\TestCase;

class AutoScout24Test extends TestCase
{
    private function fixtureHtml(): string
    {
        return file_get_contents(__DIR__.'/Fixtures/search-page.html');
    }

    private function fixtureSearchJson(): string
    {
        return file_get_contents(__DIR__.'/Fixtures/search-results.json');
    }

    private function fixtureDetailJson(): string
    {
        return file_get_contents(__DIR__.'/Fixtures/detail.json');
    }

    /**
     * @param  Response[]  $responses
     */
    private function createClient(array $responses): AutoScout24
    {
        $mock = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        return new AutoScout24(httpClient: $client);
    }

    /**
     * @param  Response[]  $responses
     * @param  array<int, array<string, mixed>>  $history
     */
    private function createClientWithHistory(array $responses, array &$history): AutoScout24
    {
        $mock = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $handler->push(Middleware::history($history));
        $client = new Client(['handler' => $handler]);

        return new AutoScout24(httpClient: $client);
    }

    // --- Build ID Extraction ---

    public function test_fetches_build_id_from_html_page(): void
    {
        $history = [];
        $client = $this->createClientWithHistory([
            new Response(200, [], $this->fixtureHtml()),
            new Response(200, [], $this->fixtureSearchJson()),
        ], $history);

        $client->search(new CarSearchCriteria);

        $this->assertCount(2, $history);
        $this->assertStringContainsString('/lst', (string) $history[0]['request']->getUri());
    }

    public function test_caches_build_id_in_memory(): void
    {
        $history = [];
        $client = $this->createClientWithHistory([
            new Response(200, [], $this->fixtureHtml()),
            new Response(200, [], $this->fixtureSearchJson()),
            new Response(200, [], $this->fixtureSearchJson()),
        ], $history);

        $client->search(new CarSearchCriteria);
        $client->search(new CarSearchCriteria);

        // Build ID page fetched only once, then 2 search requests
        $this->assertCount(3, $history);
    }

    public function test_caches_build_id_in_psr16_cache(): void
    {
        $cache = new ArrayCache;

        $mock = new MockHandler([
            new Response(200, [], $this->fixtureHtml()),
            new Response(200, [], $this->fixtureSearchJson()),
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handler]);

        $client = new AutoScout24(httpClient: $httpClient, cache: $cache);
        $client->search(new CarSearchCriteria);

        $this->assertTrue($cache->has('autoscout24:build-id'));
    }

    // --- Stale Build ID Retry ---

    public function test_retries_on_stale_build_id(): void
    {
        $oldHtml = str_replace(
            'as24-search-funnel_main-20260327180837',
            'as24-search-funnel_main-OLD',
            $this->fixtureHtml(),
        );

        $history = [];
        $client = $this->createClientWithHistory([
            new Response(200, [], $oldHtml),                   // Initial build ID fetch
            new Response(404, [], ''),                         // Search fails (stale)
            new Response(200, [], $this->fixtureHtml()),       // Fresh build ID fetch
            new Response(200, [], $this->fixtureSearchJson()), // Retry search succeeds
        ], $history);

        $result = $client->search(new CarSearchCriteria);

        $this->assertGreaterThan(0, count($result->listings));
    }

    public function test_does_not_retry_when_build_id_unchanged(): void
    {
        $this->expectException(NotFoundException::class);

        $client = $this->createClient([
            new Response(200, [], $this->fixtureHtml()), // Build ID fetch
            new Response(404, [], ''),                   // Search fails
            new Response(200, [], $this->fixtureHtml()), // Same build ID
        ]);

        $client->search(new CarSearchCriteria);
    }

    // --- Search ---

    public function test_search_sends_correct_headers(): void
    {
        $history = [];
        $client = $this->createClientWithHistory([
            new Response(200, [], $this->fixtureHtml()),
            new Response(200, [], $this->fixtureSearchJson()),
        ], $history);

        $client->search(new CarSearchCriteria);

        $searchRequest = $history[1]['request'];
        $this->assertSame('1', $searchRequest->getHeaderLine('x-nextjs-data'));
    }

    public function test_search_builds_correct_url(): void
    {
        $history = [];
        $client = $this->createClientWithHistory([
            new Response(200, [], $this->fixtureHtml()),
            new Response(200, [], $this->fixtureSearchJson()),
        ], $history);

        $client->search(new CarSearchCriteria);

        $uri = (string) $history[1]['request']->getUri();
        $this->assertStringContainsString('/_next/data/', $uri);
        $this->assertStringContainsString('/lst.json', $uri);
    }

    public function test_motorcycle_search_includes_atype(): void
    {
        $history = [];
        $client = $this->createClientWithHistory([
            new Response(200, [], $this->fixtureHtml()),
            new Response(200, [], $this->fixtureSearchJson()),
        ], $history);

        $client->search(new MotorcycleSearchCriteria);

        $uri = (string) $history[1]['request']->getUri();
        $this->assertStringContainsString('atype=B', $uri);
    }

    // --- Search All (Pagination) ---

    public function test_search_all_paginates(): void
    {
        $searchJson = json_decode($this->fixtureSearchJson(), true);
        $searchJson['pageProps']['numberOfPages'] = 2;
        $searchJson['pageProps']['numberOfResults'] = 40;

        $page1 = json_encode($searchJson);
        $page2 = json_encode($searchJson);

        $client = $this->createClient([
            new Response(200, [], $this->fixtureHtml()),
            new Response(200, [], $page1),
            new Response(200, [], $page2),
        ]);

        $listings = [];
        foreach ($client->searchAll(new CarSearchCriteria) as $listing) {
            $listings[] = $listing;
        }

        $this->assertSame(40, count($listings));
    }

    // --- Detail ---

    public function test_get_detail_by_slug(): void
    {
        $client = $this->createClient([
            new Response(200, [], $this->fixtureHtml()),
            new Response(200, [], $this->fixtureDetailJson()),
        ]);

        $detail = $client->getDetailBySlug('volkswagen-polo-test-4cf6768f-b357-4be1-8a0d-c797e6b7fe78');

        $this->assertNotEmpty($detail->id);
        $this->assertNotEmpty($detail->vehicle->make);
    }

    public function test_get_detail_by_url(): void
    {
        $history = [];
        $client = $this->createClientWithHistory([
            new Response(200, [], $this->fixtureHtml()),
            new Response(200, [], $this->fixtureDetailJson()),
        ], $history);

        $detail = $client->getDetailByUrl('https://www.autoscout24.nl/aanbod/volkswagen-polo-test-4cf6768f');

        $this->assertNotEmpty($detail->id);

        $detailUri = (string) $history[1]['request']->getUri();
        $this->assertStringContainsString('/details/', $detailUri);
        $this->assertStringNotContainsString('/aanbod/', $detailUri);
    }

    public function test_get_detail_from_listing(): void
    {
        $client = $this->createClient([
            new Response(200, [], $this->fixtureHtml()),
            new Response(200, [], $this->fixtureDetailJson()),
        ]);

        $listing = ListingFactory::make([
            'url' => '/aanbod/volkswagen-polo-test-4cf6768f',
        ]);
        $detail = $client->getDetail($listing);

        $this->assertNotEmpty($detail->id);
    }

    // --- Brands & Filter Options (from HTML) ---

    public function test_get_brands(): void
    {
        $client = $this->createClient([
            new Response(200, [], $this->fixtureHtml()),
        ]);

        $brands = $client->getBrands(VehicleType::Car);
        $this->assertNotEmpty($brands);
        $this->assertIsInt($brands[0]->value);
        $this->assertNotEmpty($brands[0]->label);
    }

    public function test_get_filter_options(): void
    {
        $client = $this->createClient([
            new Response(200, [], $this->fixtureHtml()),
        ]);

        $bodyTypes = $client->getFilterOptions(VehicleType::Car, 'bodyType');
        $this->assertNotEmpty($bodyTypes);
    }

    // --- Reset Session ---

    public function test_reset_session_clears_build_id(): void
    {
        $cache = new ArrayCache;

        $mock = new MockHandler([
            new Response(200, [], $this->fixtureHtml()),
            new Response(200, [], $this->fixtureSearchJson()),
            new Response(200, [], $this->fixtureHtml()),
            new Response(200, [], $this->fixtureSearchJson()),
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handler]);

        $client = new AutoScout24(httpClient: $httpClient, cache: $cache);
        $client->search(new CarSearchCriteria);

        $this->assertTrue($cache->has('autoscout24:build-id'));

        $client->resetSession();

        $this->assertFalse($cache->has('autoscout24:build-id'));
    }

    // --- Error Handling ---

    public function test_throws_on_non_200_build_id_fetch(): void
    {
        $this->expectException(AutoScout24Exception::class);

        $client = $this->createClient([
            new Response(500, [], 'Internal Server Error'),
        ]);

        $client->search(new CarSearchCriteria);
    }

    public function test_throws_on_invalid_json(): void
    {
        $this->expectException(AutoScout24Exception::class);
        $this->expectExceptionMessage('Failed to decode JSON');

        $client = $this->createClient([
            new Response(200, [], $this->fixtureHtml()),
            new Response(200, [], 'not json'),
        ]);

        $client->search(new CarSearchCriteria);
    }

    public function test_throws_on_invalid_url_for_detail(): void
    {
        $this->expectException(AutoScout24Exception::class);
        $this->expectExceptionMessage('Invalid URL');

        $client = $this->createClient([]);

        // parse_url returns false for path on 'http://'
        $client->getDetailByUrl('http://');
    }

    // --- Get Models ---

    public function test_get_models(): void
    {
        $client = $this->createClient([
            new Response(200, [], $this->fixtureHtml()),
        ]);

        $brands = $client->getBrands(VehicleType::Car);
        $this->assertNotEmpty($brands);

        // Use the same HTML fixture for models request
        $client = $this->createClient([
            new Response(200, [], $this->fixtureHtml()),
        ]);

        $models = $client->getModels(VehicleType::Car, $brands[0]);
        $this->assertIsArray($models);

        if ($models !== []) {
            $this->assertSame($brands[0]->value, $models[0]->makeId);
        }
    }

    // --- fetchHtml error handling ---

    public function test_throws_on_non_200_html_fetch_for_brands(): void
    {
        $this->expectException(AutoScout24Exception::class);
        $this->expectExceptionMessage('Unexpected status code 503');

        $client = $this->createClient([
            new Response(503, [], 'Service Unavailable'),
        ]);

        $client->getBrands(VehicleType::Car);
    }

    public function test_throws_on_http_exception_during_html_fetch(): void
    {
        $this->expectException(AutoScout24Exception::class);
        $this->expectExceptionMessage('Failed to fetch HTML page');

        $mock = new MockHandler([
            new ConnectException(
                'Connection refused',
                new Request('GET', '/lst'),
            ),
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handler]);

        $client = new AutoScout24(httpClient: $httpClient);
        $client->getBrands(VehicleType::Car);
    }

    // --- searchAll pagination error ---

    public function test_search_all_propagates_error_on_second_page(): void
    {
        $searchJson = json_decode($this->fixtureSearchJson(), true);
        $searchJson['pageProps']['numberOfPages'] = 3;
        $page1 = json_encode($searchJson);

        $client = $this->createClient([
            new Response(200, [], $this->fixtureHtml()),  // Build ID
            new Response(200, [], $page1),                 // Page 1 succeeds
            new Response(500, [], 'Internal Server Error'), // Page 2 fails
        ]);

        $listings = [];
        $this->expectException(AutoScout24Exception::class);

        foreach ($client->searchAll(new CarSearchCriteria) as $listing) {
            $listings[] = $listing;
        }
    }

    // --- Cache TTL ---

    public function test_cache_ttl_is_passed_to_cache_set(): void
    {
        $cache = new ArrayCache;

        $mock = new MockHandler([
            new Response(200, [], $this->fixtureHtml()),
            new Response(200, [], $this->fixtureSearchJson()),
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handler]);

        $customTtl = 7200;
        $client = new AutoScout24(httpClient: $httpClient, cache: $cache, cacheTtl: $customTtl);
        $client->search(new CarSearchCriteria);

        // The cache stores the build ID; verify it was stored with the correct TTL
        $this->assertTrue($cache->has('autoscout24:build-id'));
        $this->assertSame($customTtl, $cache->getTtl('autoscout24:build-id'));
    }

    // --- DTO round-trip ---

    public function test_listing_to_array_round_trip(): void
    {
        $data = json_decode($this->fixtureSearchJson(), true);
        $result = (new JsonParser)->parseSearchResults($data, 1);
        $listing = $result->listings[0];

        $array = $listing->toArray();

        $this->assertSame($listing->id, $array['id']);
        $this->assertSame($listing->url, $array['url']);
        $this->assertSame($listing->price->priceFormatted, $array['price']['priceFormatted']);
        $this->assertSame($listing->vehicle->make, $array['vehicle']['make']);
    }

    public function test_listing_detail_to_array_round_trip(): void
    {
        $data = json_decode($this->fixtureDetailJson(), true);
        $detail = (new JsonParser)->parseDetail($data);

        $array = $detail->toArray();

        $this->assertSame($detail->id, $array['id']);
        $this->assertSame($detail->vehicle->make, $array['vehicle']['make']);
        $this->assertSame($detail->prices->isFinalPrice, $array['prices']['isFinalPrice']);
    }

    // --- New DTO fields ---

    public function test_price_raw_is_parsed_from_formatted(): void
    {
        $data = json_decode($this->fixtureSearchJson(), true);
        $result = (new JsonParser)->parseSearchResults($data, 1);
        $listing = $result->listings[0];

        $this->assertNotNull($listing->price->priceRaw);
        $this->assertIsInt($listing->price->priceRaw);
        $this->assertGreaterThan(0, $listing->price->priceRaw);
    }

    public function test_vehicle_mileage_raw_is_parsed_from_formatted(): void
    {
        $data = json_decode($this->fixtureSearchJson(), true);
        $result = (new JsonParser)->parseSearchResults($data, 1);
        $listing = $result->listings[0];

        if ($listing->vehicle->mileageInKm !== '') {
            $this->assertNotNull($listing->vehicle->mileageInKmRaw);
            $this->assertIsInt($listing->vehicle->mileageInKmRaw);
            $this->assertGreaterThan(0, $listing->vehicle->mileageInKmRaw);
        }
    }
}
