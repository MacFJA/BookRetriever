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

use MacFJA\BookRetriever\Helper\ConfigurableInterface;
use function sprintf;
use function urlencode;

/**
 * Class FeedBooks.
 *
 * @suppress PhanUnreferencedClass
 */
class FeedBooks extends OpenPublicationDistributionSystemProvider implements ConfigurableInterface
{
    public const API_PATTERN = 'https://www.feedbooks.com/search.atom?lang=%s&query=%s';

    protected $language = 'en';

    public function setLanguage(string $language): FeedBooks
    {
        $this->language = $language;

        return $this;
    }

    public function getCode(): string
    {
        return 'feedbooks';
    }

    public static function getLabel(): string
    {
        return 'FeedBooks';
    }

    public function configure(array $parameters): void
    {
        $this->setLanguage($parameters['language'] ?? 'en');
    }

    protected function getApiUrlForIsbn(string $isbn): string
    {
        return sprintf(static::API_PATTERN, urlencode($this->language), urlencode($isbn));
    }
}
