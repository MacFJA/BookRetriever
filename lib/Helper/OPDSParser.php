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

use DateTime;
use function is_string;
use MacFJA\BookRetriever\SearchResult\SearchResultBuilder;
use MacFJA\BookRetriever\SearchResultInterface;
use function simplexml_load_string;

/**
 * Helper to transform Open Publication Distribution System result into Search result.
 *
 * @see SearchResultInterface
 * @see \MacFJA\BookRetriever\Provider\OpenPublicationDistributionSystemProvider
 */
class OPDSParser
{
    use XPathOperationTrait;

    /**
     * @return SearchResultInterface[]
     */
    public function parseAtom(string $response): array
    {
        $atom = @simplexml_load_string($response);

        if (false === $atom) {
            return [];
        }

        $nodes = $atom->xpath('//*[local-name()="entry"]');

        $results = [];

        foreach ($nodes as $node) {
            $result = [
                'title' => $this->extract($node, '//*[name()="title"]/text()'),
                'isbn' => $this->extract($node, '//*[name()="dcterms:identifier"]/text()'),
                'authors' => $this->extract($node, '//*[name()="author"]/*[name()="name"]/text()', true),
                'language' => $this->extract($node, '//*[name()="dcterms:language"]/text()'),
                'publisher' => $this->extract($node, '//*[name()="dcterms:publisher"]/text()'),
                'pages' => $this->extract($node, '//*[name()="dcterms:extent"]/text()'),
                'genres' => $this->extract($node, '//*[name()="category"]/@label', true),
                'summary' => $this->extract($node, '//*[name()="summeary"]/text()'),
                'cover' => $this->extract($node, '//*[name()="link" and starts-with(@type,"image") and not(contains(@rel,"thumbnail"))]/@href'),
                'opds_link' => $this->extract($node, '//*[name()="id"]/text()', true),
            ];
            $date = $this->extract($node, '//*[name()="published"]/text()');
            if (is_string($date)) {
                $date = DateTime::createFromFormat(DateTime::ATOM, $date);
                $result['publicationDate'] = $date;
            }

            $results[] = SearchResultBuilder::createFromArray($result);
        }

        return $results;
    }
}
