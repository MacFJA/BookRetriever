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

use function array_keys;
use function array_map;
use function array_values;
use function implode;
use function sprintf;
use function urlencode;

/**
 * The Library of Congress search engine provider.
 *
 * Available search are:
 *  - ISBN
 *  - EAN
 *  - Title
 *  - Publisher
 *  - Language
 *  - UPC
 *  - LCCN (Library of Congress Control Number)
 * They can be mixed together.
 *
 * @author MacFJA
 * @license MIT
 *
 * @suppress PhanUnreferencedClass
 */
class LOC extends SearchRetrieveUrlProvider
{
    public const API_PATTERN = 'http://lx2.loc.gov:210/lcdb'.
        '?version=1.1'.
        '&operation=searchRetrieve'.
        '&maximumRecords=5'.
        '&recordSchema=mods'.
        '&query=%s';

    public function getCode(): string
    {
        return 'loc';
    }

    public static function getLabel(): string
    {
        return 'The Library of Congress';
    }

    public function getSearchableField(): array
    {
        return ['isbn', 'ean', 'title', 'publisher', 'language', 'upc', 'lccn'];
    }

    protected function getParams(array $terms): string
    {
        $mapping = [
            'isbn' => 'dc.identifier',
            'ean' => 'dc.identifier',
            'upc' => 'dc.identifier',
            'lccn' => 'dc.identifier',
            'title' => 'dc.title',
            'publisher' => 'dc.publisher',
            'language' => 'dc.language',
        ];

        $params = $this->mapParams($terms, $mapping);

        $query = array_map(function ($key, $value) {
            return $key.'='.urlencode($value);
        }, array_keys($params), array_values($params));

        return implode('%20and%20', $query);
    }

    protected function getApiUrlForTerms(array $terms): string
    {
        return sprintf(self::API_PATTERN, $this->getParams($terms));
    }
}
