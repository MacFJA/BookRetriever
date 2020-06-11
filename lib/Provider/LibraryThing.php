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

use DateTime;
use MacFJA\BookRetriever\Helper\ConfigurableInterface;
use MacFJA\BookRetriever\Helper\HttpClientAwareInterface;
use MacFJA\BookRetriever\Helper\HttpClientAwareTrait;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResult\SearchResultBuilder;
use function reset;
use function simplexml_load_string;
use SimpleXMLElement;
use function sprintf;
use function urlencode;

/**
 * LibraryThing search engine provider.
 *
 * Available search are:
 *  - ISBN
 *  - EAN
 * They can only be use one at a time.
 *
 * You need an account to use this provider.
 * (https://www.librarything.com/services/keys.php)
 *
 * @author MacFJA
 * @license MIT
 *
 * @suppress PhanUnreferencedClass
 */
class LibraryThing implements ProviderInterface, ConfigurableInterface, HttpClientAwareInterface
{
    use IsbnOnlyTrait;
    use HttpClientAwareTrait;

    public const API_URL_PATTERN = 'http://www.librarything.com/services/rest/1.1/'.
        '?method=librarything.ck.getwork'.
        '&isbn=%s'.
        '&apikey=%s';

    /** @var string */
    protected $apiKey = '';

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function getCode(): string
    {
        return 'library-thing';
    }

    public static function getLabel(): string
    {
        return 'LibraryThing';
    }

    public function searchIsbn(string $isbn): array
    {
        $client = $this->getHttpClient();
        $response = $client->sendRequest($this->createHttpRequest(
            'GET',
            sprintf(self::API_URL_PATTERN, urlencode($isbn), urlencode($this->apiKey))
        ));

        $xml = @simplexml_load_string($response->getBody()->getContents());

        if (false === $xml) {
            return [];
        }

        $results = [];
        foreach ($xml->xpath('//*[name()="item"][@type="work"]') as $work) {
            $xpathValue = $work->xpath('//*[name()="commonknowledge"]//*[name()="field"][@type="16"]//*[name()="fact"]/text()');

            if (false === $xpathValue) {
                continue;
            }

            $publicationDate = reset($xpathValue);

            if ($publicationDate instanceof SimpleXMLElement) {
                $publicationDate = DateTime::createFromFormat('y', (string) $publicationDate['timestamp']);
            }
            $results[] = SearchResultBuilder::createFromArray([
                'authors' => [(string) $work->author],
                'title' => (string) $work->title,
                'libraything_link' => (string) $work->url,
                'publicationDate' => $publicationDate,
                'average_rating' => $work->rating,
            ]);
        }

        return $results;
    }

    public function configure(array $parameters): void
    {
        $this->setApiKey($parameters['api_key']);
    }
}
