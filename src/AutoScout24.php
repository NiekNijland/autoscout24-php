<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use NiekNijland\AutoScout24\Data\Brand;
use NiekNijland\AutoScout24\Data\FilterOption;
use NiekNijland\AutoScout24\Data\Listing;
use NiekNijland\AutoScout24\Data\ListingDetail;
use NiekNijland\AutoScout24\Data\Model;
use NiekNijland\AutoScout24\Data\SearchQuery;
use NiekNijland\AutoScout24\Data\SearchResult;
use NiekNijland\AutoScout24\Data\VehicleType;
use NiekNijland\AutoScout24\Exception\AutoScout24Exception;
use NiekNijland\AutoScout24\Exception\NotFoundException;
use NiekNijland\AutoScout24\Parser\JsonParser;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

class AutoScout24 implements AutoScout24Interface
{
    private const string BASE_URL = 'https://www.autoscout24.nl';

    private const string CACHE_KEY = 'autoscout24:build-id';

    private const string USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

    private ?string $buildId = null;

    private readonly JsonParser $parser;

    public function __construct(
        private readonly ClientInterface $httpClient = new Client,
        private readonly ?CacheInterface $cache = null,
        private readonly int $cacheTtl = 3600,
    ) {
        $this->parser = new JsonParser;
    }

    public function search(SearchQuery $query): SearchResult
    {
        return $this->withRetry(function () use ($query): SearchResult {
            $buildId = $this->getBuildId();
            $params = $query->toQueryParams()->toArray();
            $queryString = http_build_query($params);
            $url = self::BASE_URL."/_next/data/{$buildId}/lst.json?{$queryString}";

            $data = $this->fetchJson($url);

            return $this->parser->parseSearchResults($data, $query->page());
        });
    }

    /**
     * Auto-paginate through all pages, yielding each Listing lazily.
     *
     * WARNING: This method makes one HTTP request per page with no delay between requests.
     * For large result sets (hundreds of pages), this may trigger rate limiting or IP blocking
     * from AutoScout24. Use $delayMs to add a pause between page requests.
     *
     * @param  int  $delayMs  Milliseconds to sleep between page requests (0 = no delay)
     * @return Generator<int, Listing>
     */
    public function searchAll(SearchQuery $query, int $delayMs = 0): Generator
    {
        $result = $this->search($query);
        yield from $result->listings;

        while ($result->hasNextPage()) {
            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }

            $query = $query->withPage($result->currentPage + 1);
            $result = $this->search($query);
            yield from $result->listings;
        }
    }

    /**
     * @return Brand[]
     */
    public function getBrands(VehicleType $type): array
    {
        $taxonomy = $this->fetchTaxonomy($type);

        return $this->parser->parseBrands($taxonomy);
    }

    /**
     * @return Model[]
     */
    public function getModels(VehicleType $type, Brand $brand): array
    {
        $taxonomy = $this->fetchTaxonomy($type, ['mmmv' => "{$brand->value}|||"]);

        return $this->parser->parseModels($taxonomy, $brand);
    }

    /**
     * @return FilterOption[]
     */
    public function getFilterOptions(VehicleType $type, string $taxonomyKey): array
    {
        $taxonomy = $this->fetchTaxonomy($type);

        return $this->parser->parseFilterOptions($taxonomy, $taxonomyKey);
    }

    public function getDetail(Listing $listing): ListingDetail
    {
        $slug = $this->extractSlugFromUrl($listing->url);

        return $this->getDetailBySlug($slug);
    }

    public function getDetailBySlug(string $slug): ListingDetail
    {
        return $this->withRetry(function () use ($slug): ListingDetail {
            $buildId = $this->getBuildId();
            $url = self::BASE_URL."/_next/data/{$buildId}/details/{$slug}.json";

            $data = $this->fetchJson($url);

            return $this->parser->parseDetail($data);
        });
    }

    public function getDetailByUrl(string $url): ListingDetail
    {
        $slug = $this->extractSlugFromUrl($url);

        return $this->getDetailBySlug($slug);
    }

    public function resetSession(): void
    {
        $this->buildId = null;
        $this->cache?->delete(self::CACHE_KEY);
    }

    /**
     * Fetch taxonomy data from the HTML page (taxonomy is embedded in __NEXT_DATA__, not in _next/data JSON).
     *
     * @param  array<string, string>  $extraParams
     * @return array<string, mixed>
     */
    private function fetchTaxonomy(VehicleType $type, array $extraParams = []): array
    {
        $params = [];
        $atype = $type->articleTypeParam();
        if ($atype !== null) {
            $params['atype'] = $atype;
        }

        $params = array_merge($params, $extraParams);

        $path = '/lst';
        if ($params !== []) {
            $path .= '?'.http_build_query($params);
        }

        $html = $this->fetchHtml($path);
        $pageProps = $this->parser->extractPagePropsFromHtml($html);

        return $pageProps['taxonomy'] ?? [];
    }

    private function fetchHtml(string $path): string
    {
        try {
            /** @var ResponseInterface $response */
            $response = $this->httpClient->sendRequest(
                new Request('GET', self::BASE_URL.$path, [
                    'User-Agent' => self::USER_AGENT,
                ]),
            );
        } catch (\Throwable $e) {
            throw new AutoScout24Exception('Failed to fetch HTML page: '.$e->getMessage(), 0, $e);
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new AutoScout24Exception("Unexpected status code {$statusCode} when fetching HTML page", $statusCode);
        }

        return (string) $response->getBody();
    }

    private function getBuildId(): string
    {
        if ($this->buildId !== null) {
            return $this->buildId;
        }

        if ($this->cache !== null) {
            $cached = $this->cache->get(self::CACHE_KEY);
            if (is_string($cached) && $cached !== '') {
                $this->buildId = $cached;

                return $cached;
            }
        }

        return $this->fetchBuildId();
    }

    private function fetchBuildId(): string
    {
        $html = $this->fetchHtml('/lst');
        $buildId = $this->parser->extractBuildId($html);

        $this->buildId = $buildId;
        $this->cache?->set(self::CACHE_KEY, $buildId, $this->cacheTtl);

        return $buildId;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchJson(string $url): array
    {
        try {
            /** @var ResponseInterface $response */
            $response = $this->httpClient->sendRequest(
                new Request('GET', $url, [
                    'x-nextjs-data' => '1',
                    'User-Agent' => self::USER_AGENT,
                ]),
            );
        } catch (\Throwable $e) {
            throw new AutoScout24Exception('HTTP request failed: '.$e->getMessage(), 0, $e);
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode === 404) {
            throw new NotFoundException("Resource not found: {$url}", 404);
        }

        if ($statusCode !== 200) {
            throw new AutoScout24Exception("Unexpected status code {$statusCode}", $statusCode);
        }

        $body = (string) $response->getBody();

        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new AutoScout24Exception('Failed to decode JSON response: '.$e->getMessage(), 0, $e);
        }

        if (! is_array($data)) {
            throw new AutoScout24Exception('Expected JSON object in response');
        }

        return $data;
    }

    /**
     * Retry a callback once if a NotFoundException suggests a stale buildId.
     *
     * Only NotFoundException triggers a retry (the buildId may have changed).
     * Other transient errors (5xx, network timeouts) are NOT retried and will
     * propagate immediately. Callers should implement their own retry/backoff
     * for those cases if needed.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function withRetry(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (NotFoundException $e) {
            $previousBuildId = $this->buildId;

            $this->buildId = null;
            $this->cache?->delete(self::CACHE_KEY);

            $newBuildId = $this->fetchBuildId();

            if ($newBuildId === $previousBuildId) {
                throw $e;
            }

            return $callback();
        }
    }

    private function extractSlugFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path)) {
            throw new AutoScout24Exception("Invalid URL: {$url}");
        }

        return ltrim(str_starts_with($path, '/aanbod/') ? substr($path, 8) : $path, '/');
    }
}
