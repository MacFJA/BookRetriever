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

namespace MacFJA\BookRetriever\Helper;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Base implementation of HttpClientAwareInterface.
 *
 * @see HttpClientAwareInterface
 */
trait HttpClientAwareTrait
{
    /** @var null|StreamFactoryInterface */
    private $httpStreamFactory;

    /** @var null|ClientInterface */
    private $httpClient;

    /** @var null|RequestFactoryInterface */
    private $httpRequestFactory;

    public function setHttpClient(ClientInterface $client): void
    {
        $this->httpClient = $client;
    }

    public function setHttpRequestFactory(RequestFactoryInterface $requestFactory): void
    {
        $this->httpRequestFactory = $requestFactory;
    }

    /**
     * @suppress PhanUnreferencedPublicMethod
     */
    public function setHttpStreamFactory(StreamFactoryInterface $streamFactory): void
    {
        $this->httpStreamFactory = $streamFactory;
    }

    protected function createHttpStream(string $body): StreamInterface
    {
        return ($this->httpStreamFactory ?? Psr17FactoryDiscovery::findStreamFactory())->createStream($body);
    }

    protected function getHttpClient(): ClientInterface
    {
        return $this->httpClient ?? Psr18ClientDiscovery::find();
    }

    /**
     * @param string|UriInterface $uri
     */
    protected function createHttpRequest(string $method, $uri): RequestInterface
    {
        return ($this->httpRequestFactory ?? Psr17FactoryDiscovery::findRequestFactory())
            ->createRequest($method, $uri)
            ->withAddedHeader('User-Agent', 'PHP/BookRetriever');
    }
}
