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

namespace MacFJA\BookRetriever;

/**
 * Contract for a book search engine provider.
 *
 * To ease implementation, they are several traits:
 * IsbnOnlyTrait, IsbnAsCriteriaTrait, OneCriteriaOnlyTrait
 * and base classes:
 * OpenPublicationDistributionSystemProvider, SearchRetrieveUrlProvider
 *
 * @see \MacFJA\BookRetriever\Provider\IsbnOnlyTrait
 * @see \MacFJA\BookRetriever\Provider\IsbnAsCriteriaTrait
 * @see \MacFJA\BookRetriever\Provider\OneCriteriaOnlyTrait
 * @see \MacFJA\BookRetriever\Provider\OpenPublicationDistributionSystemProvider
 * @see \MacFJA\BookRetriever\Provider\SearchRetrieveUrlProvider
 */
interface ProviderInterface
{
    /**
     * @return SearchResultInterface[]
     */
    public function search(array $criteria): array;

    /**
     * @return SearchResultInterface[]
     */
    public function searchIsbn(string $isbn): array;

    /**
     * @return string[]
     */
    public function getSearchableField(): array;

    public function getCode(): string;

    public static function getLabel(): string;
}
