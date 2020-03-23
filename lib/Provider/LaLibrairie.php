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
use DateTime;
use function http_build_query;
use MacFJA\BookRetriever\Helper\HtmlGetter;
use MacFJA\BookRetriever\Helper\HttpClientAwareInterface;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResult\SearchResultBuilder;
use function reset;
use SimpleXMLElement;

/**
 * LaLibrairie search engine provider. (FR).
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
class LaLibrairie implements ProviderInterface, HttpClientAwareInterface
{
    use IsbnOnlyTrait;
    use UseHtmlGetterTrait;

    public const WEBPAGE_URL = 'https://www.lalibrairie.com/livres/recherche.html';

    public const WEBPAGE_BASE_URL = 'https://www.lalibrairie.com';

    /** @var HtmlGetter */
    protected $htmlGetter;

    /**
     * LaLibrairie constructor.
     */
    public function __construct(HtmlGetter $htmlGetter)
    {
        $this->htmlGetter = $htmlGetter;
    }

    public function getCode(): string
    {
        return 'lalibrairie';
    }

    public static function getLabel(): string
    {
        return 'Lalibrairie.com (HTML)';
    }

    /**
     * {@inheritdoc}
     */
    public function searchIsbn(string $isbn): array
    {
        $client = $this->getHttpClient();
        $request = $this->createHttpRequest('POST', static::WEBPAGE_URL)->withBody(
            $this->createHttpStream(http_build_query([
                'rapid-search' => $isbn,
            ]))
        );
        $response = $client->sendRequest($request);

        if (!$response->hasHeader('location')) {
            return [];
        }
        $location = $response->getHeader('location');
        $location = reset($location);

        if ('/' === $location) {
            return [];
        }

        $xml = $this->htmlGetter->getWebpageAsXml(static::WEBPAGE_BASE_URL.$location);

        $allMeta = $xml->xpath('//*[local-name()="meta"]');
        $itemProperties = $xml->xpath('//*[@itemprop]');

        $result = $this->handleMeta(['authors' => []], $allMeta);
        $result = $this->handleItemProperties($result, $itemProperties);

        return [SearchResultBuilder::createFromArray($result)];
    }

    protected function getHtmlGetter(): HtmlGetter
    {
        return $this->htmlGetter;
    }

    /**
     * @param SimpleXMLElement[] $allMeta
     *
     * @return array<string, string>
     */
    private function handleMeta(array $result, array $allMeta): array
    {
        $metaMapping = [
            'og:image' => 'cover',
            'og:url' => 'lalibrairie_link',
            'og:title' => 'title',
        ];
        foreach ($allMeta as $meta) {
            $property = (string) $meta['property'];
            $content = (string) $meta['content'];

            if (array_key_exists($property, $metaMapping)) {
                $result[$metaMapping[$property]] = $content;
            }
        }

        return $result;
    }

    /**
     * @param SimpleXMLElement[] $itemProperties
     */
    private function handleItemProperties(array $result, array $itemProperties): array
    {
        $itemPropertyMapping = [
            'price' => null,
            'priceCurrency' => null,
            'name' => 'title',
            'editor' => 'publisher',
            'numberOfPages' => 'pages',
            'gtin13' => 'isbn',
        ];
        foreach ($itemProperties as $itemProperty) {
            $property = (string) $itemProperty['itemprop'];

            if (array_key_exists($property, $itemPropertyMapping) && null === $itemPropertyMapping[$property]) {
                continue;
            }

            switch ($property) {
                case 'author':
                    $result['authors'][] = (string) $itemProperty;

                    break;
                case 'image':
                    $result['cover'] = self::WEBPAGE_BASE_URL.(string) $itemProperty['src'];

                    break;
                case 'weight':
                    $result['weight'] = (string) $itemProperty.'g';

                    break;
                case 'datePublished':
                    $result['publicationDate'] = DateTime::createFromFormat('d/m/Y', (string) $itemProperty);

                    break;
                default:
                    $result[$itemPropertyMapping[$property] ?? $property] = (string) $itemProperty;
            }
        }

        return $result;
    }
}
