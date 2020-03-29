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

use function array_key_exists;
use function array_keys;
use function count;
use DateTime;
use function explode;
use function http_build_query;
use function is_string;
use MacFJA\BookRetriever\Helper\HtmlGetter;
use MacFJA\BookRetriever\Helper\HttpClientAwareInterface;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResult\SearchResultBuilder;
use function preg_match;
use SimpleXMLElement;
use function sprintf;

/**
 * AbeBooks search engine provider.
 *
 * It's a Web scraping (https://en.wikipedia.org/wiki/Web_scraping).
 * Available search are:
 *  - ISBN
 *  - EAN
 *  - Author
 *  - Title
 * They can be mixed together.
 *
 * @author MacFJA
 * @license MIT
 *
 * @suppress PhanUnreferencedClass
 */
class AbeBooks implements ProviderInterface, HttpClientAwareInterface
{
    use IsbnAsCriteriaTrait;
    use UseHtmlGetterTrait;

    public const WEBPAGE_BASE_PATTERN = 'https://www.abebooks.com/servlet/SearchResults?%s';

    protected const SEARCH_MAPPING = [
        'author' => 'an',
        'authors' => 'an',
        'title' => 'tn',
        'isbn' => 'isbn',
        'ean' => 'isbn',
    ];

    /** @var HtmlGetter */
    protected $htmlGetter;

    /**
     * AbeBooks constructor.
     */
    public function __construct(HtmlGetter $htmlGetter)
    {
        $this->htmlGetter = $htmlGetter;
    }

    public function getCode(): string
    {
        return 'abebooks-html';
    }

    public static function getLabel(): string
    {
        return 'AbeBooks (HTML)';
    }

    public function search(array $criteria): array
    {
        $query = [];
        foreach ($criteria as $key => $value) {
            if (!array_key_exists($key, self::SEARCH_MAPPING)) {
                continue;
            }
            $query[self::SEARCH_MAPPING[$key]] = $value;
        }

        if (0 === count($query)) {
            return [];
        }

        $query['sts'] = 't';

        $xml = $this->getHtmlGetter()->getWebpageAsXml(sprintf(static::WEBPAGE_BASE_PATTERN, http_build_query($query)));

        return $this->extractFromXml($xml);
    }

    public function getSearchableField(): array
    {
        return array_keys(self::SEARCH_MAPPING);
    }

    protected function extractFromXml(SimpleXMLElement $xml): array
    {
        $books = $xml->xpath('//*[@itemtype="http://schema.org/Book"]');

        $results = [];

        $mapping = [
            '' => null,
            'price' => null,
            'priceCurrency' => null,
            'itemCondition' => null,
            'availability' => null,
            'name' => 'title',
            'bookFormat' => 'format',
            'about' => 'description',
            // publisher, isbn are the same
        ];

        foreach ($books as $book) {
            $allMeta = $book->xpath('.//*[local-name()="meta"]');
            $result = [];
            foreach ($allMeta as $meta) {
                $property = (string) $meta['itemprop'];
                $content = (string) $meta['content'];

                if (array_key_exists($property, $mapping) && null === $mapping[$property]) {
                    continue;
                }

                switch ($property) {
                    case 'author':
                        $result['authors'] = explode(',', $content);

                        break;
                    case 'datePublished':
                        $result['publicationDate'] = DateTime::createFromFormat('Y', $content);

                        break;
                    default:
                        $result[$mapping[$property] ?? $property] = $content;
                }
            }
            $bookString = $book->asXML();
            if (is_string($bookString) && 1 === preg_match(
                '#https://pictures.abebooks.com/isbn/[\dxX]+-\w{2}\.\w{3,4}#',
                $bookString,
                $matches
            )) {
                $result['cover'] = $matches[0] ?? null;
            }
            $results[] = SearchResultBuilder::createFromArray($result);
        }

        return $results;
    }

    protected function getHtmlGetter(): HtmlGetter
    {
        return $this->htmlGetter;
    }
}
