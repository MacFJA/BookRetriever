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

use DTS\eBaySDK\Finding\Enums\AckValue;
use DTS\eBaySDK\Finding\Services\FindingService;
use DTS\eBaySDK\Finding\Types\BaseFindingServiceResponse;
use DTS\eBaySDK\Finding\Types\FindItemsByKeywordsRequest;
use DTS\eBaySDK\Finding\Types\FindItemsByProductRequest;
use DTS\eBaySDK\Finding\Types\ProductId;
use function in_array;
use MacFJA\BookRetriever\Helper\ConfigurableInterface;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResult\SearchResultBuilder;
use function strtoupper;

/**
 * Ebay search engine provider.
 *
 * Available search are:
 *  - ISBN
 *  - EAN
 * They can only be use one at a time.
 *
 * You need an account to use this provider.
 * (https://developer.ebay.com/signin)
 *
 * @author MacFJA
 * @license MIT
 *
 * @suppress PhanUnreferencedClass
 */
class Ebay implements ProviderInterface, ConfigurableInterface
{
    use IsbnAsCriteriaTrait;
    use OneCriteriaOnlyTrait;

    /** @var string */
    protected $devId = '';

    /** @var string */
    protected $appId = '';

    /** @var string */
    protected $certId = '';

    public function setDevId(string $devId): void
    {
        $this->devId = $devId;
    }

    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    public function setCertId(string $certId): void
    {
        $this->certId = $certId;
    }

    public function getCode(): string
    {
        return 'ebay';
    }

    public static function getLabel(): string
    {
        return 'Ebay';
    }

    public function getSearchableField(): array
    {
        return ['ean', 'upc', 'isbn', 'epid', 'keywords', '*'];
    }

    public function configure(array $parameters): void
    {
        $this->setAppId($parameters['app_id']);
        $this->setCertId($parameters['cert_id']);
        $this->setDevId($parameters['dev_id']);
    }

    /**
     * @psalm-suppress DocblockTypeContradiction
     * @psalm-suppress RedundantConditionGivenDocblockType
     */
    protected function oneCriteriaSearch(string $field, $value): array
    {
        $response = $this->getResponse($field, (string) $value);

        if (empty($response->errorMessage)) {
            return [];
        }
        if (!(AckValue::C_SUCCESS === $response->ack)) {
            return [];
        }
        if (!isset($response->searchResult->item)) {
            return [];
        }

        $result = [];

        foreach ($response->searchResult->item as $searchItem) {
            $result[] = SearchResultBuilder::createFromArray([
                'title' => $searchItem->title,
                'cover' => $searchItem->pictureURLLarge,
                'subtitle' => $searchItem->subtitle,
                'ebay_link' => $searchItem->viewItemURL,
            ]);
        }

        return $result;
    }

    private function getResponse(string $field, string $value): BaseFindingServiceResponse
    {
        $service = new FindingService(['credentials' => [
            'devId' => $this->devId,
            'appId' => $this->appId,
            'certId' => $this->certId,
        ]]);

        $request = new FindItemsByKeywordsRequest();
        $request->keywords = $value;

        if (in_array($field, ['ean', 'upc', 'isbn',  'epid'], true)) {
            $request = new FindItemsByProductRequest();
            $productId = new ProductId();
            $productId->value = $value;
            $productId->type = 'epid' === $field
                ? 'ReferenceID'
                : strtoupper($field);
            $request->productId = $productId;
        }

        return  ($request instanceof FindItemsByProductRequest)
            ? $service->findItemsByProduct($request)
            : $service->findItemsByKeywords($request);
    }
}
