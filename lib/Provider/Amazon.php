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

use function array_filter;
use function array_key_exists;
use function count;
use function current;
use DateTime;
use function implode;
use InvalidArgumentException;
use function key;
use MacFJA\BookRetriever\Helper\ConfigurableInterface;
use MacFJA\BookRetriever\Helper\HttpClientAwareInterface;
use MacFJA\BookRetriever\Helper\HttpClientAwareTrait;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResult\SearchResultBuilder;
use function simplexml_load_string;
// @phan-suppress-next-line PhanUnreferencedUseNormal
use SimpleXMLElement;
use function sprintf;
use function strtolower;
use function urlencode;

/**
 * Amazon search engine provider.
 *
 * Available search are:
 *  - ISBN
 *  - EAN
 *  - ASIN (Amazon identifier)
 *  - Title
 *  - Publisher
 *  - Author
 * They can be mixed together.
 *
 * Books are search in several countries: fr, us, de, ca, es, jp, uk.
 *
 * An account is mandatory to use this provider.
 * (https://docs.aws.amazon.com/AWSECommerceService/latest/DG/becomingAssociate.html)
 *
 * @author MacFJA
 * @license MIT
 *
 * @suppress PhanUnreferencedClass
 */
class Amazon implements ProviderInterface, ConfigurableInterface, HttpClientAwareInterface
{
    use IsbnAsCriteriaTrait;
    use HttpClientAwareTrait;

    protected const API_BASE_URL_PATTERN = 'http://webservices.amazon.%s/onca/xml'.
        '?AWSAccessKeyId=%s'.
        '&AssociateTag=%s'.
        '&Service=AWSECommerceService'.
        '&ResponseGroup=ItemAttributes,Images'.
        '&SearchIndex=Books&';

    protected const API_EAN_URL_PATTERN = 'Operation=ItemLookup&IdType=EAN&ItemId=%s';

    protected const API_ISBN_URL_PATTERN = 'Operation=ItemLookup&IdType=ISBN&ItemId=%s';

    protected const API_ASIN_URL_PATTERN = 'Operation=ItemLookup&IdType=ASIN&ItemId=%s';

    protected const API_SEARCH_URL_PATTERN = 'Operation=ItemSearch&IncludeReviewsSummary=false&%s';

    protected const COUNTRY_DOMAINS = ['fr', 'us', 'com', 'de', 'ca', 'es', 'co.jp', 'co.uk'];

    /** @var string */
    protected $accessKey;

    /** @var string */
    protected $associateTag;

    public function setAccessKey(string $accessKey): void
    {
        $this->accessKey = $accessKey;
    }

    public function setAssociateTag(string $associateTag): void
    {
        $this->associateTag = $associateTag;
    }

    public function getCode(): string
    {
        return 'amazon';
    }

    public static function getLabel(): string
    {
        return 'Amazon';
    }

    public function search(array $criteria): array
    {
        if (1 === count($criteria)) {
            return $this->oneCriteria(key($criteria), current($criteria));
        }

        $params = [];
        foreach ($criteria as $field => $value) {
            $params[] = $this->getUrlSearchTerm($field, $value);
        }
        $params = array_filter($params);

        return $this->doRequest(sprintf(self::API_SEARCH_URL_PATTERN, implode('&', $params)));
    }

    public function getSearchableField(): array
    {
        return ['isbn', 'ean', 'asin', 'title', 'publisher', 'author'];
    }

    public function configure(array $parameters): void
    {
        $this->setAccessKey($parameters['access_key'] ?? '');
        $this->setAssociateTag($parameters['associated_tag'] ?? '');
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getUrlSearchTerm(string $field, string $value): string
    {
        $mapping = [
            'title' => 'Title',
            'publisher' => 'Publisher',
            'author' => 'Author',
        ];

        if (!array_key_exists($field, $mapping)) {
            throw new InvalidArgumentException('$field is not a value search key');
        }

        return urlencode($mapping[$field]).'='.urlencode($value);
    }

    protected function oneCriteria(string $field, $value): array
    {
        switch (strtolower($field)) {
            case 'isbn':
                return $this->doRequest(sprintf(self::API_ISBN_URL_PATTERN, $value));
            case 'ean':
                return $this->doRequest(sprintf(self::API_EAN_URL_PATTERN, $value));
            case 'asin':
                return $this->doRequest(sprintf(self::API_ASIN_URL_PATTERN, $value));
            default:
                return $this->doRequest(sprintf(self::API_SEARCH_URL_PATTERN, $this->getUrlSearchTerm($field, $value)));
        }
    }

    private function doRequest(string $queryUrl): array
    {
        $client = $this->getHttpClient();
        $results = [];
        foreach (self::COUNTRY_DOMAINS as $domain) {
            $response = $client->sendRequest(
                $this->createHttpRequest('GET', sprintf(
                    self::API_BASE_URL_PATTERN, $domain,
                    $this->accessKey, $this->associateTag).$queryUrl
                )
            );
            $response = simplexml_load_string($response->getBody()->getContents());
            if (!isset($response->Item) || null === $response->Item) {
                continue;
            }

            /** @var SimpleXMLElement $item */
            foreach ($response->Item->children() as $item) {
                $results[] = SearchResultBuilder::createFromArray([
                    'authors' => [(string) $item->ItemAttributes->Author],
                    'amazon_id' => (string) $item->ASIN,
                    'isbn' => (string) $item->ItemAttributes->EAN,
                    'format' => (string) $item->ItemAttributes->Binding,
                    'genres' => [(string) $item->ItemAttributes->Genre],
                    'pages' => (int) $item->ItemAttributes->NumberOfPages,
                    'publisher' => (string) $item->ItemAttributes->Publisher,
                    'publicationDate' => DateTime::createFromFormat(
                        'Y-m-d',
                        (string) $item->ItemAttributes->PublicationDate
                    ),
                    'title' => (string) $item->ItemAttributes->Title,
                    'cover' => (string) $item->Images->LargeImage->URL,
                    'amazon_link' => (string) $item->DetailPageURL,
                ]);
            }
        }

        return $results;
    }
}
