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

namespace MacFJA\BookRetriever\Tests\Unit\Provider;

use function count;
use Isbn\Isbn;
use MacFJA\BookRetriever\Helper\HtmlGetter;
use MacFJA\BookRetriever\Provider\AntoineOnline;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResultInterface;
use MacFJA\BookRetriever\Tests\Unit\BaseProviderTestCase;

/**
 * @covers \MacFJA\BookRetriever\Provider\AntoineOnline
 * @psalm-suppress InternalMethod
 *
 * @uses \MacFJA\BookRetriever\ProviderConfigurator
 * @uses \MacFJA\BookRetriever\SearchResult\SearchResult
 * @uses \MacFJA\BookRetriever\SearchResult\SearchResultBuilder
 * @uses \MacFJA\BookRetriever\Helper\HtmlGetter
 *
 * @internal
 */
class AntoineOnlineTest extends BaseProviderTestCase
{
    public function dataProvider(string $testName): array
    {
        if ('testSearchIsbn' === $testName) {
            return [
                [['antoineonline.search', 'antoineonline.response'], '9782253006329', 1, 'Vingt mille lieues sous les mers'],
            ];
        }

        return [];
    }

    protected function getProvider(): ProviderInterface
    {
        return new AntoineOnline(new HtmlGetter(), new Isbn());
    }

    /**
     * @param SearchResultInterface[] $results
     */
    protected function additionalAssertion(string $testName, array $testParams, array $results): void
    {
        if ('testSearchIsbn' === $testName) {
            $this->assertEquals($testParams[1], $results[0]->getIsbn());
            $this->assertGreaterThan(0, count($results[0]->getAuthors()));
            $this->assertNotEmpty($results[0]->getCover());
            $this->assertArrayHasKey('antoineonline_link', $results[0]->getAdditional());
        }
    }
}
