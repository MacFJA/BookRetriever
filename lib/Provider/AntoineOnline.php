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

use DOMElement;
use DOMNodeList;
use Isbn\Isbn;
use MacFJA\BookRetriever\Helper\HtmlGetter;
use MacFJA\BookRetriever\Helper\HttpClientAwareInterface;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResult\SearchResultBuilder;
use function reset;
use function sprintf;
use function strlen;
use function urlencode;

/**
 * AntoineOnline search engine provider.
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
class AntoineOnline implements ProviderInterface, HttpClientAwareInterface
{
    use IsbnOnlyTrait;
    use UseHtmlGetterTrait;

    public const WEBPAGE_SEARCH_PATTERN = 'https://www.antoineonline.com/listing.aspx?q=%s&type=1';

    public const WEBPAGE_BASE_URL = 'https://www.antoineonline.com';

    /** @var HtmlGetter */
    protected $htmlGetter;

    /** @var Isbn */
    protected $isbnTool;

    /**
     * AntoineOnline constructor.
     */
    public function __construct(HtmlGetter $htmlGetter, Isbn $isbnTool)
    {
        $this->htmlGetter = $htmlGetter;
        $this->isbnTool = $isbnTool;
    }

    public function getCode(): string
    {
        return 'antoineonline';
    }

    public static function getLabel(): string
    {
        return 'AntoineOnline (HTML)';
    }

    public function searchIsbn(string $isbn): array
    {
        $isbn = $this->isbnTool->hyphens->removeHyphens($isbn);
        $client = $this->getHttpClient();
        $requestBody = sprintf(static::WEBPAGE_SEARCH_PATTERN, urlencode($isbn));
        $request = $this->createHttpRequest('POST', $requestBody)
            ->withAddedHeader('content-length', (string) strlen($requestBody));
        $response = $client->sendRequest($request);

        if ($response->getStatusCode() < 300 || $response->getStatusCode() > 399) {
            return [];
        }
        $location = $response->getHeader('location');
        $location = reset($location);

        $xml = $this->getHtmlGetter()->getWebpageAsDom(static::WEBPAGE_BASE_URL.$location);

        $allMeta = $xml->getElementsByTagName('meta');
        $resultDiv = $xml->getElementById('ctl00_cph1_ProductMainPage');

        if (null === $resultDiv) {
            return [];
        }
        $allInput = $resultDiv->getElementsByTagName('input');

        $result = ['antoineonline_link' => static::WEBPAGE_BASE_URL.$location, 'isbn' => $isbn];
        $result = $this->handleNode($result, $allMeta, [$this, 'metaNodeVisitor']);
        $result = $this->handleNode($result, $allInput, [$this, 'inputNodeVisitor']);

        return [SearchResultBuilder::createFromArray($result)];
    }

    protected function getHtmlGetter(): HtmlGetter
    {
        return $this->htmlGetter;
    }

    private function handleNode(array $result, DOMNodeList $nodes, callable $visitor): array
    {
        for ($index = 0; $index < $nodes->length; ++$index) {
            $item = $nodes->item($index);
            $result = $visitor($item, $result);
        }

        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private static function inputNodeVisitor(DOMElement $input, array $result): array
    {
        $value = $input->getAttribute('value');
        if (!empty($value) && 'author' === $input->getAttribute('class')) {
            $result['authors'] = [$value];
        }
        if ('product-title' === $input->getAttribute('class')) {
            $result['title'] = $value;
        }

        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function metaNodeVisitor(DOMElement $meta, array $result): array
    {
        if (!$meta->hasAttribute('name')) {
            return $result;
        }

        $property = $meta->getAttribute('name');
        $content = $meta->getAttribute('content');

        switch ($property) {
            case 'og:image':
                $result['cover'] = $content;

                break;
            case 'og:title':
                $result['title'] = $content;

                break;
        }

        return $result;
    }
}
