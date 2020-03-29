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

use function json_decode;
use MacFJA\BookRetriever\Helper\ConfigurableInterface;
use MacFJA\BookRetriever\Helper\HttpClientAwareInterface;
use MacFJA\BookRetriever\Helper\HttpClientAwareTrait;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResult\SearchResultBuilder;
use function urlencode;

/**
 * ISBNDb search engine provider.
 *
 * Available search are:
 *  - ISBN
 *  - EAN
 * They can only be use one at a time.
 *
 * @author MacFJA
 * @license MIT
 *
 * @suppress PhanUnreferencedClass
 */
class ISBNDb implements ProviderInterface, ConfigurableInterface, HttpClientAwareInterface
{
    use IsbnOnlyTrait;
    use HttpClientAwareTrait;

    /** @var string */
    protected $apiKey;

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function getCode(): string
    {
        return 'isbndb';
    }

    public static function getLabel(): string
    {
        return 'ISBNdb';
    }

    public function searchIsbn(string $isbn): array
    {
        $client = $this->getHttpClient();
        $request = $this->createHttpRequest('GET', 'http://api.isbndb.com/book/'.urlencode($isbn))->withHeader('x-api-key', $this->apiKey);
        $response = $client->sendRequest($request);

        if (!(200 === $response->getStatusCode())) {
            return [];
        }

        $json = json_decode($response->getBody()->getContents());
        $book = $json['book'] ?? [];

        if (empty($book)) {
            return [];
        }

        return [SearchResultBuilder::createFromArray([
            'publisher' => $book['publisher'] ?? null,
            'language' => $book['language'] ?? null,
            'title' => $book['title_long'] ?? null,
            'isbn' => $book['isbn13'] ?? null,
            'authors' => $book['authors'] ?? [],
            'dimension' => $book['dimensions'] ?? null,
        ])];
    }

    public function configure(array $parameters): void
    {
        $this->setApiKey($parameters['api_key']);
    }
}
