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

namespace MacFJA\BookRetriever;

use function count;
use MacFJA\BookRetriever\Helper\ConfigurableInterface;
use MacFJA\BookRetriever\Helper\HttpClientAwareInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Class ProviderConfigurator.
 *
 * @suppress PhanUnreferencedClass
 */
class ProviderConfigurator
{
    /** @var ProviderConfigurationInterface */
    protected $configuration;

    /** @var null|ClientInterface */
    private $httpClient;

    /** @var null|RequestFactoryInterface */
    private $httpRequestFactory;

    /** @var null|StreamFactoryInterface */
    private $httpStreamFactory;

    public function __construct(ProviderConfigurationInterface $configuration, ?ClientInterface $httpClient, ?RequestFactoryInterface $httpRequestFactory, ?StreamFactoryInterface $httpStreamFactory)
    {
        $this->configuration = $configuration;
        $this->httpClient = $httpClient;
        $this->httpRequestFactory = $httpRequestFactory;
        $this->httpStreamFactory = $httpStreamFactory;
    }

    /**
     * @suppress PhanUnreferencedPublicMethod
     */
    public function configure(ProviderInterface $provider): void
    {
        $parameters = $this->configuration->getParameters($provider);

        if ($provider instanceof ConfigurableInterface && count($parameters) > 0) {
            $provider->configure($parameters);
        }

        if ($provider instanceof HttpClientAwareInterface) {
            if ($this->httpStreamFactory instanceof StreamFactoryInterface) {
                $provider->setHttpStreamFactory($this->httpStreamFactory);
            }
            if ($this->httpRequestFactory instanceof RequestFactoryInterface) {
                $provider->setHttpRequestFactory($this->httpRequestFactory);
            }
            if ($this->httpClient instanceof ClientInterface) {
                $provider->setHttpClient($this->httpClient);
            }
        }
    }
}
