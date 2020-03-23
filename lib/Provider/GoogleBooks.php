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

use AppendIterator;
use function array_filter;
use function array_reduce;
use ArrayIterator;
use DateTime;
use EmptyIterator;
use Exception;
// @phan-suppress-next-line PhanUnreferencedUseNormal
use Generator;
use MacFJA\BookRetriever\ProviderInterface;
use MacFJA\BookRetriever\SearchResult\SearchResultBuilder;
use Scriptotek\GoogleBooks\Volume;
use stdClass;
use function strlen;

/**
 * Class GoogleBooks.
 *
 * @suppress PhanUnreferencedClass
 */
class GoogleBooks implements ProviderInterface
{
    use IsbnOnlyTrait;

    public function getCode(): string
    {
        return 'google-book-isbn';
    }

    public static function getLabel(): string
    {
        return 'Google Books';
    }

    /**
     * {@inheritdoc}
     */
    public function searchIsbn(string $isbn): array
    {
        $client = new \Scriptotek\GoogleBooks\Volumes(new \Scriptotek\GoogleBooks\GoogleBooks());

        try {
            $isbnVolume = $client->byIsbn($isbn);
            $isbnVolume = ($isbnVolume instanceof Volume) ? new ArrayIterator([$isbnVolume]) : new EmptyIterator();

            $ean = new EmptyIterator();
            if (13 === strlen($isbn)) {
                /** @var Generator $ean */
                $ean = $client->search('ean:'.$isbn);
            }

            $volumes = new AppendIterator();
            $volumes->append($ean);
            $volumes->append($isbnVolume);

            $result = [];
            foreach ($volumes as $volume) {
                $result[] = $this->parseVolume($volume);
            }

            return $result;
        } catch (Exception $exception) {
            return [];
        }
    }

    protected function parseVolume(Volume $volume)
    {
        $normalized = [
            'googlebooks_link' => $volume->selfLink,
            'title' => $volume->title,
            'authors' => $volume->authors,
            'description' => $volume->description,
            'pages' => $volume->pageCunt,
            'genres' => $volume->categories,
            'cover' => $volume->getCover(),
            'language' => $volume->language,
        ];
        $publication = $volume->publishedDate;

        $parsed = false;
        foreach (['Y-m-d', 'd-m-Y', 'Y-m', 'm-Y', 'Y'] as $format) {
            $parsed = DateTime::createFromFormat($format, $publication);
            if ($parsed instanceof DateTime) {
                break;
            }
        }
        if ($parsed instanceof DateTime) {
            $normalized['publicationDate'] = $parsed;
        }

        $identifiers = $volume->industryIdentifiers ?? [];
        $identifiers = array_reduce($identifiers, function (array $carry, stdClass $item): array {
            $carry[$item->type] = $item->identifier;

            return $carry;
        }, []);
        $isbn = $identifiers['ISBN_13'] ?? $identifiers['ISBN_10'] ?? null;
        $normalized['isbn'] = $isbn;

        return SearchResultBuilder::createFromArray(array_filter($normalized));
    }
}
