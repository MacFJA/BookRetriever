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

use function base64_encode;
use function hash_hmac;
use function is_array;
use function json_decode;
use MacFJA\BookRetriever\Helper\ConfigurableInterface;
use MacFJA\BookRetriever\Helper\HttpClientAwareInterface;
use MacFJA\BookRetriever\Helper\HttpClientAwareTrait;
use MacFJA\BookRetriever\MissingParameterException;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResult\SearchResultBuilder;
use function sprintf;

/**
 * DigitEyes search engine provider.
 *
 * Available search are:
 *  - ISBN
 *  - EAN
 * They can only be use one at a time.
 *
 * You need an account to use this provider.
 * (https://www.digit-eyes.com/cgi-bin/digiteyes.cgi?action=signup)
 *
 * @author MacFJA
 * @license MIT
 *
 * @suppress PhanUnreferencedClass
 */
class DigitEyes implements ProviderInterface, ConfigurableInterface, HttpClientAwareInterface
{
    use IsbnOnlyTrait;
    use HttpClientAwareTrait;

    public const API_PATTERN = 'http://digit-eyes.com/gtin/v2_0/'.
        '?upc_code=%s'.
        '&app_key=%s'.
        '&signature=%s'.
        '&language=%s'.
        '&field_names=%s';

    /** @var string */
    private $apiKey = '';

    /** @var string */
    private $appCode = '';

    /** @var string */
    private $language = 'en';

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function setAppCode(string $appCode): void
    {
        $this->appCode = $appCode;
    }

    public function getCode(): string
    {
        return 'digit-eyes';
    }

    public static function getLabel(): string
    {
        return 'DigitEyes';
    }

    public function searchIsbn(string $isbn): array
    {
        MissingParameterException::throwIfMissing($this, [
            'api_key' => $this->apiKey,
            'app_code' => $this->appCode,
            'language' => $this->language,
        ], 'You need an account to use this provider: https://www.digit-eyes.com/cgi-bin/digiteyes.cgi?action=signup');

        $client = $this->getHttpClient();
        $response = $client->sendRequest(
            $this->createHttpRequest('GET', sprintf(
                static::API_PATTERN,
                $isbn,
                $this->appCode,
                $this->getSignature($isbn),
                $this->language,
                'description,image,thumbnail,categories'
            ))
        );

        if (!(200 === $response->getStatusCode())) {
            return [];
        }

        $json = json_decode($response->getBody()->getContents(), true);
        $results = [];

        if (!is_array($json)) {
            return [];
        }

        foreach ($json as $item) {
            $results[] = SearchResultBuilder::createFromArray([
                'cover' => $item['image'] ?? $item['thumbnail'] ?? null,
                'title' => $item['description'],
                'genres' => $item['catgeories'],
            ]);
        }

        return $results;
    }

    public function configure(array $parameters): void
    {
        $this->setApiKey($parameters['api_key'] ?? '');
        $this->setAppCode($parameters['app_code'] ?? '');
        $this->setLanguage($parameters['language'] ?? 'en');
    }

    private function getSignature(string $ean): string
    {
        return base64_encode(hash_hmac('sha1', $ean, $this->apiKey, true));
    }
}
