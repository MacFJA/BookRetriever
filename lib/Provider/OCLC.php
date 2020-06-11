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
use function count;
use function current;
use function explode;
use function http_build_query;
use function key;
use MacFJA\BookRetriever\Helper\HttpClientAwareInterface;
use MacFJA\BookRetriever\Helper\HttpClientAwareTrait;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResult\SearchResultBuilder;
use Psr\Http\Message\ResponseInterface;
use function simplexml_load_string;
use function sprintf;
use function strpos;
use function strtolower;
use function urlencode;

/**
 * Online Computer Library Center search engine provider.
 *
 * Available search are:
 *  - ISBN
 *  - EAN
 *  - Title
 *  - Author
 *  - OCLC
 * They can be mixed together.
 *
 * @author MacFJA
 * @license MIT
 *
 * @suppress PhanUnreferencedClass
 */
class OCLC implements ProviderInterface, HttpClientAwareInterface
{
    use IsbnAsCriteriaTrait;
    use HttpClientAwareTrait;

    public const API_ID_URL_PATTERN = 'http://classify.oclc.org/classify2/Classify?%s=%s&summary=true';

    public const API_SEARCH_URL_PATTERN = 'http://classify.oclc.org/classify2/Classify?%s&summary=true';

    public function oneFieldSearch(string $field, string $value): array
    {
        $client = $this->getHttpClient();

        switch (strtolower($field)) {
            case 'isbn':
            case 'ean':
                $url = sprintf(self::API_ID_URL_PATTERN, 'isbn', urlencode($value));

                break;
            case 'oclc':
                $url = sprintf(self::API_ID_URL_PATTERN, 'oclc', urlencode($value));

                break;
            default:
                $url = sprintf(self::API_SEARCH_URL_PATTERN, urlencode(strtolower($field)).'='.urlencode($value));
        }

        return $this->parseResult($client->sendRequest($this->createHttpRequest('GET', $url)));
    }

    public function getCode(): string
    {
        return 'oclc';
    }

    public static function getLabel(): string
    {
        return 'Online Computer Library Center';
    }

    public function search(array $criteria): array
    {
        if (1 === count($criteria)) {
            return $this->oneFieldSearch((string) key($criteria), current($criteria) ?: '0');
        }

        $client = $this->getHttpClient();

        $url = sprintf(self::API_SEARCH_URL_PATTERN, http_build_query($criteria));

        return $this->parseResult($client->sendRequest($this->createHttpRequest('GET', $url)));
    }

    public function getSearchableField(): array
    {
        return ['isbn', 'ean', 'oclc', 'author', 'title'];
    }

    protected function parseResult(ResponseInterface $response): array
    {
        $xml = @simplexml_load_string($response->getBody()->getContents());

        if (false === $xml) {
            return [];
        }

        $results = [];
        foreach ($xml->xpath('//*[local-name()="work"]') as $work) {
            $authors = explode(' | ', (string) $work['author']);

            $results[] = SearchResultBuilder::createFromArray([
                'authors' => array_filter($authors, function (string $author): bool {
                    return (
                        false === strpos($author, 'Illustrator') &&
                        false === strpos($author, 'Translator')
                    ) || 0 < strpos($author, 'Author');
                }),
                'illustrators' => array_filter($authors, function (string $author): bool {
                    return 0 < strpos($author, 'Illustrator');
                }),
                'translators' => array_filter($authors, function (string $author): bool {
                    return 0 < strpos($author, 'Translator');
                }),
                'format' => $work['format'],
                'title' => $work['title'],
            ]);
        }

        return $results;
    }
}
