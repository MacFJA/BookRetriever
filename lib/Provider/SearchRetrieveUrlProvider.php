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

use function array_key_exists;
use MacFJA\BookRetriever\Helper\HttpClientAwareInterface;
use MacFJA\BookRetriever\Helper\HttpClientAwareTrait;
use MacFJA\BookRetriever\Helper\SRUParser;
use MacFJA\BookRetriever\ProviderInterface;

abstract class SearchRetrieveUrlProvider implements ProviderInterface, HttpClientAwareInterface
{
    use IsbnAsCriteriaTrait;
    use HttpClientAwareTrait;

    /** @var SRUParser */
    protected $sru;

    /**
     * LOC constructor.
     */
    public function __construct(SRUParser $sru)
    {
        $this->sru = $sru;
    }

    /**
     * {@inheritdoc}
     */
    public function search(array $criteria): array
    {
        $client = $this->getHttpClient();
        $response = $client->sendRequest($this->createHttpRequest(
            'GET',
            $this->getApiUrlForTerms($criteria)
        ));

        if (!(200 === $response->getStatusCode())) {
            return [];
        }
        $source = $response->getBody()->getContents();

        return $this->sru->parseSRU($source);
    }

    protected function mapParams(array $terms, array $mapping): array
    {
        $query = [];
        foreach ($terms as $field => $value) {
            if (!array_key_exists($field, $mapping)) {
                continue;
            }
            $query[$mapping[$field]] = $value;
        }

        return $query;
    }

    abstract protected function getApiUrlForTerms(array $terms): string;
}
