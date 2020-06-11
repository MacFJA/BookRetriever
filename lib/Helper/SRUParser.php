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

use function assert;
use DateTime;
use function is_string;
use MacFJA\BookRetriever\SearchResult\SearchResultBuilder;
use function preg_replace;
use function simplexml_load_string;
use SimpleXMLElement;

/**
 * Helper to transform Search Retrieve Url result into Search result.
 *
 * @see SearchResultInterface
 * @see \MacFJA\BookRetriever\Provider\SearchRetrieveUrlProvider
 */
class SRUParser
{
    use XPathOperationTrait;

    /**
     * @psalm-suppress RedundantCondition
     */
    public function parseSRU(string $source): array
    {
        $source = preg_replace('/xmlns="[^"]+"/', '', $source);
        $xml = @simplexml_load_string($source);

        if (false === $xml) {
            return [];
        }

        $results = [];
        foreach ($xml->xpath('//*[local-name()="recordData"]/mods') as $mods) {
            assert($mods instanceof SimpleXMLElement);
            $mods->registerXPathNamespace('n', 'http://www.loc.gov/mods/v3');

            $normalized = [
                'title' => $this->extract($mods, './titleInfo/title/text()'),
                'subtitle' => $this->extract($mods, './titleInfo/subTitle/text()'),
                'publisher' => $this->extract($mods, './originInfo[@eventType="publisher"]/publisher/text()'),
                'authors' => $this->extract($mods, './name/namePart/text()', true),
                'format' => $this->extract($mods, './physicalDescription/form[authority="marcform"]/text()'),
                'isbn' => $this->extract($mods, './identifier[@type="isbn"]/text()'),
                'language' => $this->extract($mods, './language/languageTerm/text()'),
                'genres' => $this->extract($mods, './genre/text()', true),
            ];

            $date = $this->extract($mods, '/originInfo[@eventType="publisher"]/dateIssued/text()');
            if (is_string($date)) {
                $date = DateTime::createFromFormat('Y', $date);
                $normalized['publicationDate'] = $date;
            }
            $results[] = SearchResultBuilder::createFromArray($normalized);
        }

        return $results;
    }
}
