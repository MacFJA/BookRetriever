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

use function assert;
use DOMElement;
use Isbn\Isbn;
use MacFJA\BookRetriever\Helper\HtmlGetter;
use MacFJA\BookRetriever\Helper\HttpClientAwareInterface;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResult\SearchResultBuilder;
use function sprintf;
use function urlencode;

/**
 * EuroBuch search engine provider. (FR_ch).
 *
 * It's a Web scraping (https://en.wikipedia.org/wiki/Web_scraping).
 * Available search are:
 *  - ISBN
 *  - EAN
 * They can only be use one at a time.
 *
 * @author MacFJA
 * @license MIT
 *
 * @suppress PhanUnreferencedClass
 */
class EuroBuch implements ProviderInterface, HttpClientAwareInterface
{
    use IsbnOnlyTrait;
    use UseHtmlGetterTrait;

    public const WEBPAGE_PATTERN = 'https://fr.eurobuch.ch/livre/isbn/%s.html';

    /** @var HtmlGetter */
    protected $htmlGetter;

    /** @var Isbn */
    private $isbnTool;

    /**
     * EuroBuch constructor.
     */
    public function __construct(HtmlGetter $htmlGetter, Isbn $isbnTool)
    {
        $this->htmlGetter = $htmlGetter;
        $this->isbnTool = $isbnTool;
    }

    public function getCode(): string
    {
        return 'eurobuch';
    }

    public static function getLabel(): string
    {
        return 'EuroBuch.ch (HTML)';
    }

    public function searchIsbn(string $isbn): array
    {
        $xml = $this->getHtmlGetter()->getWebpageAsDom(sprintf(static::WEBPAGE_PATTERN, urlencode($this->isbnTool->hyphens->removeHyphens($isbn))));

        if (null === $xml->getElementById('book_details_content')) {
            return [];
        }

        $cover = null;
        $coverContainer = $xml->getElementById('book_details_cover');
        if ($coverContainer instanceof DOMElement) {
            $cover = $coverContainer->getElementsByTagName('img');
            if ($cover->length > 0) {
                $cover = $cover->item(0);
                assert($cover instanceof DOMElement);
                $cover = $cover->getAttribute('src');
            }
        }

        return [
            SearchResultBuilder::createFromArray([
                'title' => $this->htmlGetter->getInnerText($xml->getElementById('book_details_title')),
                'cover' => $cover,
                'authors' => [$this->htmlGetter->getInnerText($xml->getElementById('book_details_author'))],
                'description' => $this->htmlGetter->getInnerText($xml->getElementById('book_details_description')),
                'eurobuch_link' => sprintf(static::WEBPAGE_PATTERN, $isbn),
                'isbn' => $isbn,
            ]),
        ];
    }

    protected function getHtmlGetter(): HtmlGetter
    {
        return $this->htmlGetter;
    }
}
