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

use DateTime;
use function is_array;
use MacFJA\BookRetriever\Helper\ConfigurableInterface;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResult\SearchResultBuilder;
use function ob_clean;
use function ob_start;
// @phan-suppress-next-line PhanUnreferencedUseNormal
use SimpleXMLElement;
use function strtolower;

/**
 * GoodReads search engine provider.
 *
 * Available search are:
 *  - ISBN
 *  - EAN
 *  - Title
 *  - Author
 *  - Id (GoodReads Identifier)
 * They can only be use one at a time.
 *
 * You need an account to use this provider.
 * (https://www.goodreads.com/user/new)
 *
 * @author MacFJA
 * @license MIT
 *
 * @suppress PhanUnreferencedClass
 */
class GoodReads implements ProviderInterface, ConfigurableInterface
{
    use OneCriteriaOnlyTrait;
    use IsbnAsCriteriaTrait;

    /** @var string */
    protected $apiKey = '';

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function oneCriteriaSearch(string $field, $value): array
    {
        $response = $this->getResponse($field, (string) $value);

        $results = [];

        foreach ($response as $item) {
            if (!isset($item->results)) {
                continue;
            }
            /** @var SimpleXMLElement $work */
            foreach ($item->results->children() as $work) {
                $results[] = SearchResultBuilder::createFromArray([
                    'title' => (string) $work->best_book->title,
                    'cover' => (string) $work->best_book->image_url,
                    'books_count' => $work->books_count,
                    'publicationDate' => DateTime::createFromFormat(
                        'Y/m/d',
                        (int) $work->original_publication_year.'/'.
                        ((string) ($work->original_publication_month ?? 1)).'/'.
                        ((string) ($work->original_publication_day ?? 1))
                    ),
                    'average_rating' => $work->average_rating,
                    'authors' => [(string) $work->best_book->author->name],
                ]);
            }
        }

        return $results;
    }

    public function getCode(): string
    {
        return 'good-reads';
    }

    public static function getLabel(): string
    {
        return 'GoodReads';
    }

    public function getSearchableField(): array
    {
        return ['isbn', 'ean', 'title', 'author', 'id'];
    }

    public function configure(array $parameters): void
    {
        $this->setApiKey($parameters['api_key']);
    }

    protected function getByISBN(string $isbn): array
    {
        $goodReads = new \Nicat\GoodReads\GoodReads($this->apiKey);
        /** @var SimpleXMLElement $response */
        $response = $goodReads->getBookByISBN($isbn);

        if (!isset($response->id) || null === $response->id) {
            return [];
        }

        $authors = [
            'authors' => [],
            'illustrators' => [],
            'translators' => [],
        ];
        $authorMapping = [
            'traducteur' => 'translators',
            'translator' => 'translators',
            'illustrator' => 'illustrators',
            'illustrateur' => 'illustrators',
        ];
        /** @var SimpleXMLElement $author */
        foreach ($response->authors->children() as $author) {
            $type = $authorMapping[strtolower((string) $author->role)] ?? 'author';
            $authors[$type][] = (string) $author->name;
        }
        $genres = [];
        foreach ($response->popular_shelves->children() as $genre) {
            $genres[] = (string) $genre['name'];
        }

        return [
            SearchResultBuilder::createFromArray([
                'title' => (string) $response->title,
                'isbn' => (string) $response->isbn13,
                'amazon_id' => (string) $response->asin,
                'cover' => (string) $response->image_url,
                'other_publication_date' => DateTime::createFromFormat(
                    'Y/m/d',
                    (int) $response->publication_year.'/'.
                    ((string) ($response->publication_month ?? 1)).'/'.
                    ((string) ($response->publication_day ?? 1))
                ),
                'publisher' => (string) $response->publisher,
                'language' => (string) $response->language_code,
                'description' => (string) $response->description,
                'books_count' => (int) $response->work->books_count,
                'publicationDate' => DateTime::createFromFormat(
                    'Y/m/d',
                    (int) $response->work->original_publication_year.'/'.
                    ((string) ($response->work->original_publication_month ?? 1)).'/'.
                    ((string) ($response->work->original_publication_day ?? 1))
                ),
                'average_rating' => (float) $response->average_rating,
                'pages' => (int) $response->num_pages,
                'format' => (string) $response->format,
                'goodreads_link' => (string) $response->url,
                'authors' => $authors['authors'],
                'illustrators' => $authors['illustrators'],
                'translators' => $authors['translators'],
                'genres' => $genres,
                'goodreads_id' => (string) $response->id,
            ]),
        ];
    }

    private function getResponse(string $field, string $value): array
    {
        $goodReads = new \Nicat\GoodReads\GoodReads($this->apiKey);

        ob_start();
        switch (strtolower($field)) {
            case 'title':
                $response = $goodReads->searchBookByName($value);

                break;
            case 'author':
                $response = $goodReads->searchBookByAuthorName($value);

                break;
            case 'isbn':
            case 'ean':
                return $this->getByISBN($value);
            case 'id':
            default:
                $response = $goodReads->searchBook($value);
        }
        ob_clean();

        return is_array($response) ? $response : [$response];
    }
}
