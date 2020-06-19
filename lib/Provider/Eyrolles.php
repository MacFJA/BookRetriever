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
use function assert;
use function count;
use DateTime;
use DateTimeInterface;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use MacFJA\BookRetriever\Helper\HtmlGetter;
use MacFJA\BookRetriever\Helper\HttpClientAwareInterface;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResult\SearchResultBuilder;
use function reset;
use function sprintf;
use function trim;
use function urlencode;

/**
 * Eyrolles search engine provider. (FR).
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
class Eyrolles implements ProviderInterface, HttpClientAwareInterface
{
    use IsbnOnlyTrait;
    use UseHtmlGetterTrait;

    public const WEBPAGE_SEARCH_PATTERN = 'https://www.eyrolles.com/Accueil/Livre/%s/';

    public const WEBPAGE_BASE_URL = 'https://www.eyrolles.com';

    /** @var HtmlGetter */
    private $htmlGetter;

    public function __construct(HtmlGetter $htmlGetter)
    {
        $this->htmlGetter = $htmlGetter;
    }

    public function getHtmlGetter(): HtmlGetter
    {
        return $this->htmlGetter;
    }

    public function searchIsbn(string $isbn): array
    {
        $client = $this->getHttpClient();
        $requestUrl = sprintf(static::WEBPAGE_SEARCH_PATTERN, urlencode($isbn));
        $request = $this->createHttpRequest('GET', $requestUrl);
        $response = $client->sendRequest($request);

        if (404 === $response->getStatusCode()) {
            return [];
        }
        $location = $response->getHeader('location');

        if (0 === count($location)) {
            return [];
        }

        $location = reset($location);

        $document = $this->getHtmlGetter()->getWebpageAsDom(static::WEBPAGE_BASE_URL.$location);

        $h1Header = $document->getElementsByTagName('h1')->item(0);

        if (null === $h1Header) {
            return [];
        }

        $extractedData = $this->extractTable($document);

        $result = [
            'eyrolles_link' => $this->getMeta('og:url', $document),
            'cover' => $this->getMeta('og:image', $document),
            'title' => $h1Header->textContent,
            'subtitle' => $this->getSubtitle($document),
            'publisher' => $extractedData['Éditeur(s)'] ?? null,
            'authors' => $this->extractAuthors($document),
            'pages' => $extractedData['Nb. de pages'] ?? null,
            'dimension' => $extractedData['Format'] ?? null,
            'format' => $extractedData['Couverture'] ?? null,
            'weight' => $extractedData['Poids'] ?? null,
            'isbn' => $extractedData['EAN13'] ?? null,
            'collection' => $extractedData['Collection'] ?? null,
            'publicationDate' => $this->extractPublication($document),
            'description' => $this->extractDescription($document),
        ];

        return [SearchResultBuilder::createFromArray($result)];
    }

    public function getCode(): string
    {
        return 'eyrolles';
    }

    public static function getLabel(): string
    {
        return 'Eyrolles (HTML)';
    }

    private function extractAuthors(DOMDocument $document): array
    {
        $authorNode = $this->getItempropNode('author', 'span', $document);

        if (null === $authorNode) {
            return [];
        }

        if ($authorNode instanceof DOMElement) {
            $authorNodes = $authorNode->getElementsByTagName('span');
            $authors = [];
            for ($index = 0; $index < $authorNodes->length; ++$index) {
                $authorNode = $authorNodes->item($index);
                if (null === $authorNode) {
                    continue;
                }

                $authors[] = $authorNode->textContent;
            }

            return $authors;
        }

        return [];
    }

    private function extractDescription(DOMDocument $document): ?string
    {
        return $this->getItemprop('about', 'div', $document);
    }

    private function extractPublication(DOMDocument $document): ?DateTimeInterface
    {
        $data = $this->extractTable($document);
        if (!array_key_exists('Parution', $data)) {
            return null;
        }
        $date = DateTime::createFromFormat('d/m/Y', $data['Parution']);

        return false === $date ? null : $date;
    }

    private function getSubtitle(DOMDocument $document): ?string
    {
        return $this->getItemprop('alternativeHeadline', 'h2', $document);
    }

    /**
     * @return array<string,string>
     * @suppress PhanPossiblyUndeclaredProperty
     * @psalm-suppress RedundantConditionGivenDocblockType
     */
    private function extractTable(DOMDocument $document): array
    {
        $result = [];
        $allTableRows = $document->getElementsByTagName('tr');
        for ($index = 0; $index < $allTableRows->length; ++$index) {
            $row = $allTableRows->item($index);
            if (null === $row) {
                continue;
            }

            assert($row instanceof DOMElement);

            $rowCells = $row->getElementsByTagName('td');

            if (!(2 === $rowCells->length)) {
                continue;
            }
            $key = $rowCells->item(0)->textContent;
            $value = trim($rowCells->item(1)->textContent);
            $result[$key] = $value;
        }

        return $result;
    }

    private function getMeta(string $propertyName, DOMDocument $document): ?string
    {
        $matches = $this->filterNodeList(function (DOMNode $node) use ($propertyName): bool {
            if (!($node instanceof DOMElement)) {
                return false;
            }
            if (!$node->hasAttribute('property')) {
                return false;
            }

            return $node->getAttribute('property') === $propertyName;
        }, $document->getElementsByTagName('meta'));

        if (0 === count($matches)) {
            return null;
        }

        $node = reset($matches);
        assert($node instanceof DOMElement);

        $content = $node->getAttribute('content');

        return empty($content) ? null : $content;
    }

    private function filterNodeList(callable $callable, DOMNodeList $nodeList): array
    {
        $matches = [];
        for ($index = 0; $index < $nodeList->length; ++$index) {
            $item = $nodeList->item($index);
            if (null === $item) {
                continue;
            }
            if ($callable($item)) {
                $matches[$index] = $item;
            }
        }

        return $matches;
    }

    private function getItempropNode(string $propName, string $tagName, DOMDocument $document): ?DOMNode
    {
        $nodes = $this->filterNodeList(function (DOMNode $node) use ($propName): bool {
            if (!($node instanceof DOMElement)) {
                return false;
            }
            if (!$node->hasAttribute('itemprop')) {
                return false;
            }

            return $node->getAttribute('itemprop') === $propName;
        }, $document->getElementsByTagName($tagName));

        return reset($nodes) ?: null;
    }

    private function getItemprop(string $propName, string $tagName, DOMDocument $document): ?string
    {
        $node = $this->getItempropNode($propName, $tagName, $document);

        if (null === $node) {
            return null;
        }

        return $node->textContent;
    }
}
