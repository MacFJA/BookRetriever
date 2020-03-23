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

namespace MacFJA\BookRetriever\Tests\Unit;

use function assert;
use function func_get_args;
use Http\Mock\Client;
use MacFJA\BookRetriever\ProviderConfigurationInterface;
use MacFJA\BookRetriever\ProviderConfigurator;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResultInterface;
use MacFJA\BookRetriever\Tests\FixtureResponse;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

/**
 * Class BaseProviderTestCase.
 *
 * @uses \MacFJA\BookRetriever\ProviderConfigurator
 * @uses \MacFJA\BookRetriever\SearchResultInterface
 */
abstract class BaseProviderTestCase extends TestCase
{
    /** @var Client */
    private $httpClient;

    /** @var FixtureResponse */
    private $fixtureResponse;

    /** @var ProviderConfigurator */
    private $configurator;

    /**
     * BaseProviderTestCase constructor.
     *
     * @param string $dataName
     * @psalm-suppress InternalMethod
     * @suppress PhanAccessMethodInternal
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    protected function setUp(): void
    {
        $this->httpClient = new Client();
        assert($this->httpClient instanceof ClientInterface);
        $this->fixtureResponse = new FixtureResponse();
        $this->fixtureResponse->setResponseFactory(new Psr17Factory());
        $this->fixtureResponse->setStreamFactory(new Psr17Factory());
        $this->configurator = new ProviderConfigurator(new class() implements ProviderConfigurationInterface {
            /**
             * @suppress PhanUnusedPublicMethodParameter
             */
            public function getParameters(ProviderInterface $provider): array
            {
                return [];
            }

            /**
             * @suppress PhanUnusedPublicMethodParameter
             */
            public function isActive(ProviderInterface $provider): bool
            {
                return true;
            }
        }, $this->httpClient, null, null);

        parent::setUp();
    }

    abstract public function dataProvider($testName): array;

    /**
     * @covers \MacFJA\BookRetriever\ProviderInterface::searchIsbn
     * @dataProvider dataProvider
     */
    public function testSearchIsbn(array $fixtureNames, string $isbn, int $resultCount, string $firstTitle): void
    {
        foreach ($fixtureNames as $fixtureName) {
            $this->httpClient->addResponse($this->fixtureResponse->readCompressed(__DIR__.'/../fixtures/'.$fixtureName.'.gz'));
        }

        $provider = $this->getProvider();
        $this->configurator->configure($provider);

        $results = $provider->searchIsbn($isbn);

        $this->assertCount($resultCount, $results);
        $this->assertEquals($firstTitle, $results[0]->getTitle());
        $this->additionalAssertion(__FUNCTION__, func_get_args(), $results);
    }

    abstract protected function getProvider(): ProviderInterface;

    /**
     * @param SearchResultInterface[] $results
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @suppress PhanUnusedProtectedMethodParameter
     */
    protected function additionalAssertion(string $testName, array $testParams, array $results): void
    {
    }
}
