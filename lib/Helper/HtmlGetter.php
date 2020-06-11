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

use function array_filter;
use function array_reduce;
use DOMDocument;
use DOMNode;
use function implode;
use function iterator_to_array;
use Masterminds\HTML5;
use function simplexml_load_string;
use SimpleXMLElement;
use function trim;

/**
 * Helper to get and parse HTML page.
 */
class HtmlGetter implements HttpClientAwareInterface
{
    use HttpClientAwareTrait;

    public function getWebpageAsXml(string $url): ?SimpleXMLElement
    {
        $dom = $this->getWebpageAsDom($url);
        $xmlString = $dom->saveXML();

        $xml = @simplexml_load_string($xmlString);

        return false === $xml ? null : $xml;
    }

    public function getWebpageAsDom(string $url): DOMDocument
    {
        $request = $this->createHttpRequest('GET', $url);
        $response = $this->getHttpClient()->sendRequest($request);

        $html5 = new HTML5();

        return $html5->loadHTML($response->getBody()->getContents());
    }

    /**
     * @param null|DOMNode|SimpleXMLElement $node
     */
    public function getInnerText($node): string
    {
        if ($node instanceof DOMNode) {
            return $this->getInnerTextDom($node);
        }

        if ($node instanceof SimpleXMLElement) {
            return $this->getInnerTextXml($node);
        }

        return '';
    }

    private function getInnerTextXml(SimpleXMLElement $node): string
    {
        if (0 === $node->count()) {
            return (string) $node;
        }

        return array_reduce(
            iterator_to_array($node->children()),
            function (string $carry, SimpleXMLElement $item): string {
                return $carry.$this->getInnerTextXml($item);
            },
            ''
        );
    }

    private function getInnerTextDom(DOMNode $node): string
    {
        if (!$node->hasChildNodes()) {
            return trim($node->textContent);
        }
        $result = [];
        for ($index = 0; $index < $node->childNodes->length; ++$index) {
            $item = $node->childNodes->item($index);
            if (null === $item) {
                continue;
            }
            $result[] = $this->getInnerTextDom($item);
        }

        return implode("\n", array_filter($result));
    }
}
