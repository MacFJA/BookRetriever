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

use function http_build_query;
use function sprintf;

/**
 * Class COPAC.
 *
 * @suppress PhanUnreferencedClass
 */
class COPAC extends SearchRetrieveUrlProvider
{
    public const BASE_API_URL = 'http://discover.libraryhub.jisc.ac.uk:210/discover?operation=searchRetrieve&version=1.1&query=%s&recordSchema=mods&maximumRecords=1';

    public function getCode(): string
    {
        return 'copac';
    }

    public static function getLabel(): string
    {
        return 'COCAP*';
    }

    public function getSearchableField(): array
    {
        return [
            'isbn', 'author', 'authors', 'title', 'ean', 'issn', 'publisher', 'language',
        ];
    }

    protected function getParams(array $terms): string
    {
        $mapping = [
            'isbn' => 'bath.isbn',
            'author' => 'dc.author',
            'authors' => 'dc.author',
            'title' => 'dc.title',
            'ean' => 'bath.isbn',
            'issn' => 'bath.issn',
            'publisher' => 'dc.publisher',
            'language' => 'dc.language',
        ];

        return http_build_query($this->mapParams($terms, $mapping));
    }

    protected function getApiUrlForTerms(array $terms): string
    {
        return sprintf(self::BASE_API_URL, $this->getParams($terms));
    }
}
