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

use function count;
use DateTime;
use function is_array;
use function is_string;
use Isbn\Isbn;
use function json_decode;
use MacFJA\BookRetriever\Helper\HttpClientAwareInterface;
use MacFJA\BookRetriever\Helper\HttpClientAwareTrait;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResult\SearchResultBuilder;
use function sprintf;
use function strpos;

/**
 * Random House search engine provider.
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
class RandomHouse implements ProviderInterface, HttpClientAwareInterface
{
    use IsbnOnlyTrait;
    use HttpClientAwareTrait;

    public const API_PATTERN = 'https://reststop.randomhouse.com/resources/titles/%s/';

    /** @var Isbn */
    protected $isbnTool;

    /**
     * RandomHouse constructor.
     */
    public function __construct(Isbn $isbnTool)
    {
        $this->isbnTool = $isbnTool;
    }

    public function getCode(): string
    {
        return 'randomhouse';
    }

    public static function getLabel(): string
    {
        return 'Random House';
    }

    public function searchIsbn(string $isbn): array
    {
        $isbn13 = $this->isbnTool->check->is10($isbn) ? $this->isbnTool->translate->to13($isbn) : $isbn;
        $value = $this->isbnTool->hyphens->removeHyphens($isbn13);
        $client = $this->getHttpClient();

        $response = $client->sendRequest($this->createHttpRequest('GET', sprintf(static::API_PATTERN, $value)));

        if (!(200 === $response->getStatusCode())) {
            return [];
        }

        $json = json_decode($response->getBody()->getContents(), true);

        if (!is_array($json)) {
            return [];
        }

        $result = [
            'authors' => $json['authorweb'],
            'format' => $json['formatname'],
            'isbn' => $json['isbn'],
            'keywords' => $json['keyword'],
            'pages' => $json['pages'],
            'title' => $json['titleweb'],
            'randomhouse_link' => $json['links'],
            'publisher' => $json['imprint'],
            'author_bio' => $json['authorbio'],
            'randomhouse_id' => $json['workid'],
        ];

        $date = $json['onsaledate'] ?? null;
        if (is_string($date)) {
            $date = DateTime::createFromFormat('m/d/Y', $date);
            $result['publicationDate'] = $date;
        }

        $genre = $this->getGenres($json);
        if (count($genre)) {
            $result['genres'] = $genre;
        }

        return [SearchResultBuilder::createFromArray($result)];
    }

    private function getGenres(array $json): array
    {
        $genre = [];
        foreach ($json as $key => $content) {
            if (0 === strpos($key, 'subjectcategorydescription')) {
                $genre[] = $content;

                continue;
            }
            if ('themes' === $key && !empty($content)) {
                $genre[] = $content;
            }
        }

        return $genre;
    }
}
