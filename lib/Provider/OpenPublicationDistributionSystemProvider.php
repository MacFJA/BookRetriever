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

use MacFJA\BookRetriever\Helper\HttpClientAwareInterface;
use MacFJA\BookRetriever\Helper\HttpClientAwareTrait;
use MacFJA\BookRetriever\Helper\OPDSParser;
use MacFJA\BookRetriever\ProviderInterface;

abstract class OpenPublicationDistributionSystemProvider implements ProviderInterface, HttpClientAwareInterface
{
    use IsbnOnlyTrait;
    use HttpClientAwareTrait;

    /** @var OPDSParser */
    protected $opds;

    /**
     * OpenPublicationDistributionSystemProvider constructor.
     */
    public function __construct(OPDSParser $opds)
    {
        $this->opds = $opds;
    }

    public function searchIsbn(string $isbn): array
    {
        $client = $this->getHttpClient();
        $response = $client->sendRequest($this->createHttpRequest('GET', $this->getApiUrlForIsbn($isbn)));

        return $this->opds->parseAtom($response->getBody()->getContents());
    }

    abstract protected function getApiUrlForIsbn(string $isbn): string;
}
