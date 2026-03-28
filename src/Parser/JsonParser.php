<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Parser;

use NiekNijland\AutoScout24\Data\Brand;
use NiekNijland\AutoScout24\Data\FilterOption;
use NiekNijland\AutoScout24\Data\Listing;
use NiekNijland\AutoScout24\Data\ListingDetail;
use NiekNijland\AutoScout24\Data\Model;
use NiekNijland\AutoScout24\Data\SearchResult;
use NiekNijland\AutoScout24\Exception\AutoScout24Exception;

class JsonParser
{
    public function extractBuildId(string $html): string
    {
        if (preg_match('/"buildId":"([^"]+)"/', $html, $matches) !== 1) {
            throw new AutoScout24Exception('Could not extract buildId from HTML');
        }

        return $matches[1];
    }

    /**
     * Extract the __NEXT_DATA__ JSON from an HTML page and return the pageProps.
     *
     * Note: The regex relies on the `id="__NEXT_DATA__"` attribute to locate the script tag.
     * The `(.*?)` non-greedy match with `/s` modifier scans for the first closing `</script>`
     * tag after the match. This is safe for well-formed Next.js pages but could match
     * incorrectly if the JSON payload itself contains a literal `</script>` string.
     *
     * @return array<string, mixed>
     */
    public function extractPagePropsFromHtml(string $html): array
    {
        if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $html, $matches) !== 1) {
            throw new AutoScout24Exception('Could not extract __NEXT_DATA__ from HTML');
        }

        try {
            $data = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new AutoScout24Exception('Failed to decode __NEXT_DATA__ JSON: '.$e->getMessage(), 0, $e);
        }

        if (! is_array($data)) {
            throw new AutoScout24Exception('Expected JSON object in __NEXT_DATA__');
        }

        return $data['props']['pageProps'] ?? [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function parseSearchResults(array $data, int $currentPage): SearchResult
    {
        $pageProps = $this->extractPageProps($data);

        $listings = array_map(
            static fn (array $item): Listing => Listing::fromArray($item),
            $pageProps['listings'] ?? [],
        );

        return new SearchResult(
            listings: $listings,
            totalCount: (int) ($pageProps['numberOfResults'] ?? 0),
            currentPage: $currentPage,
            numberOfPages: (int) ($pageProps['numberOfPages'] ?? 0),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function parseDetail(array $data): ListingDetail
    {
        $pageProps = $this->extractPageProps($data);

        $listingDetails = $pageProps['listingDetails'] ?? null;
        if ($listingDetails === null) {
            throw new AutoScout24Exception('Missing listingDetails in response');
        }

        return ListingDetail::fromArray($listingDetails);
    }

    /**
     * @param  array<string, mixed>  $taxonomy
     * @return Brand[]
     */
    public function parseBrands(array $taxonomy): array
    {
        $makesSorted = $taxonomy['makesSorted'] ?? [];

        return array_map(
            static fn (array $item): Brand => Brand::fromArray($item),
            $makesSorted,
        );
    }

    /**
     * @param  array<string, mixed>  $taxonomy
     * @return Model[]
     */
    public function parseModels(array $taxonomy, Brand $brand): array
    {
        $models = $taxonomy['models'] ?? [];
        $brandModels = $models[(string) $brand->value] ?? [];

        return array_map(
            static fn (array $item): Model => Model::fromArray(
                array_merge($item, ['makeId' => $item['makeId'] ?? $brand->value]),
            ),
            $brandModels,
        );
    }

    /**
     * @param  array<string, mixed>  $taxonomy
     * @return FilterOption[]
     */
    public function parseFilterOptions(array $taxonomy, string $taxonomyKey): array
    {
        $options = $taxonomy[$taxonomyKey] ?? [];

        if (! is_array($options)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static function (mixed $item): ?FilterOption {
                if (is_array($item) && isset($item['value'], $item['label'])) {
                    return FilterOption::fromArray($item);
                }

                return null;
            },
            $options,
        )));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function extractPageProps(array $data): array
    {
        $pageProps = $data['pageProps'] ?? null;
        if ($pageProps === null) {
            throw new AutoScout24Exception('Missing pageProps in response');
        }

        return $pageProps;
    }
}
