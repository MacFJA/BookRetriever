<?php

/*
 * Copyright MacFJA
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace MacFJA\BookRetriever\Provider;

use function array_filter;
use function array_map;
use function count;
use DateTime;
use InvalidArgumentException;
use function is_array;
use function is_numeric;
use function json_decode;
use MacFJA\BookRetriever\Helper\HttpClientAwareInterface;
use MacFJA\BookRetriever\Helper\HttpClientAwareTrait;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResult\SearchResultBuilder;
use function sprintf;
use function strtolower;

/**
 * Open Library Books search engine provider.
 *
 * Available search are:
 *  - ISBN
 *  - EAN
 *  - OCLC
 *  - LCCN (Library of Congress Control Number)
 *  - OLID (Open Library Identifier)
 * They can only be use one at a time.
 *
 * @author MacFJA
 * @license MIT
 *
 * @suppress PhanUnreferencedClass
 */
class OpenLibrary implements ProviderInterface, HttpClientAwareInterface
{
    use IsbnAsCriteriaTrait;
    use OneCriteriaOnlyTrait;
    use HttpClientAwareTrait;

    public const API_URL_PATTERN = 'https://openlibrary.org/api/books?bibkeys=%s:%s&format=json&jscmd=data';

    public function getCode(): string
    {
        return 'open-library';
    }

    public static function getLabel(): string
    {
        return 'Open Library Books';
    }

    public function getSearchableField(): array
    {
        return ['isbn', 'ean', 'olid', 'oclc', 'lccn'];
    }

    protected function oneCriteriaSearch(string $field, $value): array
    {
        $client = $this->getHttpClient();

        $searchType = $this->getSearchType($field);

        $response = $client->sendRequest($this->createHttpRequest('GET', sprintf(self::API_URL_PATTERN, $searchType, $value)));

        $json = json_decode($response->getBody()->getContents(), true);

        if (!is_array($json) || 0 === count($json)) {
            return [];
        }

        $results = [];

        foreach ($json as $searchResult) {
            $result = [
                'publisher' => array_filter(array_map(function (array $item) {
                    return $item['name'] ?? null;
                }, $searchResult['publishers'] ?? [])),
                'isbn' => $searchResult['identifiers']['isbn_13'] ?? $searchResult['identifiers']['isbn_10'] ?? null,
                'google_id' => $searchResult['identifiers']['google'] ?? null,
                'lccn_id' => $searchResult['identifiers']['lccn'] ?? null,
                'amazon_id' => $searchResult['identifiers']['amazon'] ?? null,
                'oclc_id' => $searchResult['identifiers']['oclc'] ?? null,
                'librarything_id' => $searchResult['identifiers']['librarything'] ?? null,
                'project_gutenberg_id' => $searchResult['identifiers']['project_gutenberg'] ?? null,
                'goodreads_id' => $searchResult['identifiers']['goodreads'] ?? null,
                'openlibrary_id' => $searchResult['identifiers']['openlibrary'] ?? null,
                'links' => $searchResult['links'] ?? null,
                'weight' => $searchResult['weight'] ?? null,
                'title' => $searchResult['title'] ?? null,
                'openlibrary_link' => $searchResult['url'] ?? null,
                'pages' => $searchResult['number_of_pages'] ?? null,
                'cover' => $searchResult['cover']['large'] ?? null,
                'genres' => array_filter(array_map(function (array $item) {
                    return $item['name'] ?? null;
                }, $searchResult['subjects'] ?? [])),
                'authors' => array_filter(array_map(function (array $item) {
                    return $item['name'] ?? null;
                }, $searchResult['authors'] ?? [])),
            ];

            $publishingDate = $searchResult['publish_date'] ?? null;
            if (is_numeric($publishingDate)) {
                $publishingDate = DateTime::createFromFormat('Y', (string) $publishingDate);
                $result['publicationDate'] = $publishingDate;
            }

            $results[] = SearchResultBuilder::createFromArray($result);
        }

        return $results;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getSearchType(string $field): string
    {
        switch (strtolower($field)) {
            case 'isbn':
            case 'ean':
                $searchType = 'ISBN';

                break;
            case 'olid':
                $searchType = 'OLID';

                break;
            case 'oclc':
                $searchType = 'OCLC';

                break;
            case 'lccn':
                $searchType = 'LCCN';

                break;
            default:
                throw new InvalidArgumentException('The field "'.$field.'" is not handled by this provider');
        }

        return $searchType;
    }
}
