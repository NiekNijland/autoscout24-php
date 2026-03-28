<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Tests;

use NiekNijland\AutoScout24\Data\Brand;
use NiekNijland\AutoScout24\Exception\AutoScout24Exception;
use NiekNijland\AutoScout24\Parser\JsonParser;
use PHPUnit\Framework\TestCase;

class JsonParserTest extends TestCase
{
    private JsonParser $parser;

    protected function setUp(): void
    {
        $this->parser = new JsonParser;
    }

    // --- Build ID Extraction ---

    public function test_extracts_build_id_from_html(): void
    {
        $html = file_get_contents(__DIR__.'/Fixtures/search-page.html');
        $buildId = $this->parser->extractBuildId($html);

        $this->assertNotEmpty($buildId);
        $this->assertStringStartsWith('as24-search-funnel_main-', $buildId);
    }

    public function test_throws_when_build_id_not_found(): void
    {
        $this->expectException(AutoScout24Exception::class);
        $this->expectExceptionMessage('Could not extract buildId from HTML');

        $this->parser->extractBuildId('<html><body>No build id here</body></html>');
    }

    // --- __NEXT_DATA__ Extraction ---

    public function test_extracts_page_props_from_html(): void
    {
        $html = file_get_contents(__DIR__.'/Fixtures/search-page.html');
        $pageProps = $this->parser->extractPagePropsFromHtml($html);

        $this->assertArrayHasKey('taxonomy', $pageProps);
        $this->assertArrayHasKey('listings', $pageProps);
        $this->assertArrayHasKey('numberOfResults', $pageProps);
    }

    public function test_throws_when_next_data_not_found(): void
    {
        $this->expectException(AutoScout24Exception::class);
        $this->expectExceptionMessage('Could not extract __NEXT_DATA__');

        $this->parser->extractPagePropsFromHtml('<html><body>No next data</body></html>');
    }

    // --- Search Results Parsing ---

    public function test_parses_search_results(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/Fixtures/search-results.json'), true);
        $result = $this->parser->parseSearchResults($data, 1);

        $this->assertSame(20, count($result->listings));
        $this->assertSame(1, $result->currentPage);
        $this->assertGreaterThan(0, $result->totalCount);
        $this->assertGreaterThan(0, $result->numberOfPages);
    }

    public function test_parses_listing_properties(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/Fixtures/search-results.json'), true);
        $result = $this->parser->parseSearchResults($data, 1);

        $listing = $result->listings[0];
        $this->assertNotEmpty($listing->id);
        $this->assertNotEmpty($listing->url);
        $this->assertNotEmpty($listing->price->priceFormatted);
        $this->assertNotEmpty($listing->vehicle->make);
        $this->assertNotEmpty($listing->vehicle->model);
        $this->assertNotEmpty($listing->location->countryCode);
        $this->assertNotEmpty($listing->seller->id);
    }

    public function test_search_result_has_next_page(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/Fixtures/search-results.json'), true);
        $result = $this->parser->parseSearchResults($data, 1);

        $this->assertTrue($result->hasNextPage());
    }

    public function test_throws_when_page_props_missing_in_search(): void
    {
        $this->expectException(AutoScout24Exception::class);
        $this->expectExceptionMessage('Missing pageProps');

        $this->parser->parseSearchResults([], 1);
    }

    // --- Detail Parsing ---

    public function test_parses_detail(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/Fixtures/detail.json'), true);
        $detail = $this->parser->parseDetail($data);

        $this->assertNotEmpty($detail->id);
        $this->assertNotEmpty($detail->vehicle->make);
        $this->assertNotEmpty($detail->vehicle->model);
        $this->assertNotEmpty($detail->prices->public->price);
        $this->assertGreaterThan(0, $detail->prices->public->priceRaw);
        $this->assertNotEmpty($detail->seller->id);
        $this->assertNotEmpty($detail->location->countryCode);
    }

    public function test_parses_detail_vehicle_properties(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/Fixtures/detail.json'), true);
        $detail = $this->parser->parseDetail($data);
        $vehicle = $detail->vehicle;

        $this->assertGreaterThan(0, $vehicle->makeId);
        $this->assertGreaterThan(0, $vehicle->modelId);
        $this->assertNotEmpty($vehicle->make);
        $this->assertNotEmpty($vehicle->model);
        $this->assertIsInt($vehicle->mileageInKmRaw);
    }

    public function test_detail_description_text_strips_html(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/Fixtures/detail.json'), true);
        $detail = $this->parser->parseDetail($data);

        if ($detail->description !== null) {
            $text = $detail->descriptionText();
            $this->assertNotNull($text);
            $this->assertStringNotContainsString('<br', $text);
            $this->assertStringNotContainsString('<strong>', $text);
        } else {
            $this->assertNull($detail->descriptionText());
        }
    }

    public function test_throws_when_listing_details_missing(): void
    {
        $this->expectException(AutoScout24Exception::class);
        $this->expectExceptionMessage('Missing listingDetails');

        $this->parser->parseDetail(['pageProps' => []]);
    }

    // --- Brand Parsing (from HTML taxonomy) ---

    public function test_parses_brands_from_taxonomy(): void
    {
        $html = file_get_contents(__DIR__.'/Fixtures/search-page.html');
        $pageProps = $this->parser->extractPagePropsFromHtml($html);
        $taxonomy = $pageProps['taxonomy'];

        $brands = $this->parser->parseBrands($taxonomy);

        $this->assertNotEmpty($brands);
        $this->assertContainsOnlyInstancesOf(Brand::class, $brands);

        $first = $brands[0];
        $this->assertIsInt($first->value);
        $this->assertNotEmpty($first->label);
    }

    // --- Model Parsing (from HTML taxonomy) ---

    public function test_parses_models_for_brand(): void
    {
        $html = file_get_contents(__DIR__.'/Fixtures/search-page.html');
        $pageProps = $this->parser->extractPagePropsFromHtml($html);
        $taxonomy = $pageProps['taxonomy'];

        $brands = $this->parser->parseBrands($taxonomy);

        // Find a brand with models
        $models = $taxonomy['models'] ?? [];
        $brandWithModels = null;
        foreach ($brands as $brand) {
            if (isset($models[(string) $brand->value]) && count($models[(string) $brand->value]) > 0) {
                $brandWithModels = $brand;
                break;
            }
        }

        if ($brandWithModels !== null) {
            $result = $this->parser->parseModels($taxonomy, $brandWithModels);
            $this->assertNotEmpty($result);
        } else {
            $this->markTestSkipped('No brand with models found in fixture');
        }
    }

    // --- Filter Options (from HTML taxonomy) ---

    public function test_parses_body_type_options(): void
    {
        $html = file_get_contents(__DIR__.'/Fixtures/search-page.html');
        $pageProps = $this->parser->extractPagePropsFromHtml($html);
        $taxonomy = $pageProps['taxonomy'];

        $bodyTypes = $this->parser->parseFilterOptions($taxonomy, 'bodyType');

        $this->assertNotEmpty($bodyTypes);
        $first = $bodyTypes[0];
        $this->assertNotEmpty($first->label);
    }

    public function test_parses_fuel_type_options(): void
    {
        $html = file_get_contents(__DIR__.'/Fixtures/search-page.html');
        $pageProps = $this->parser->extractPagePropsFromHtml($html);
        $taxonomy = $pageProps['taxonomy'];

        $fuelTypes = $this->parser->parseFilterOptions($taxonomy, 'fuelType');

        $this->assertNotEmpty($fuelTypes);
    }
}
