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

namespace MacFJA\BookRetriever\SearchResult;

use function array_filter;
use function array_merge;
use function array_unique;
use function count;
use DateTime;
use DateTimeInterface;
use Exception;
use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use MacFJA\BookRetriever\SearchResultInterface;
use function reset;
use function strlen;
use function ucfirst;

class SearchResultBuilder
{
    protected const INT_FIELDS = ['pages'];

    protected const STRING_FIELDS = ['isbn', 'title', 'series', 'format', 'dimension', 'cover'];

    protected const ARRAY_FIELDS = ['authors', 'illustrators', 'translators', 'genres', 'keywords'];

    protected const SPECIAL_ARRAY_FIELDS = ['author', 'illustrator', 'translator', 'genre', 'keyword'];

    protected const DATE_FIELDS = ['publicationDate'];

    /** @var SearchResult */
    protected $result;

    /**
     * SearchResultBuilder constructor.
     */
    public function __construct()
    {
        $this->result = new SearchResult();
    }

    /**
     * @param array|float|int|mixed|string $value
     *
     * @return $this
     */
    public function with(string $field, $value): self
    {
        $value = $this->handleArrayData($field, $value);
        if (
            $this->handleArray($field, $value)
            || $this->handleSpecialArray($field, $value)
            || $this->handleDate($field, $value)
            || $this->handleInt($field, $value)
            || $this->handleString($field, $value)
        ) {
            return $this;
        }

        $this->appendToAdditional($field, $value);

        return $this;
    }

    public function getResult(): SearchResultInterface
    {
        return $this->result;
    }

    /**
     * @param array<string,float|int|mixed|string> $data
     */
    public static function createFromArray(array $data): SearchResultInterface
    {
        $builder = new self();

        foreach (array_filter($data) as $field => $value) {
            $builder->with($field, $value);
        }

        return $builder->getResult();
    }

    /**
     * @param array|mixed $value
     */
    private function handleArray(string $field, $value): bool
    {
        if (!in_array($field, self::ARRAY_FIELDS, true)) {
            return false;
        }
        $methodName = 'set'.ucfirst($field);

        if (!is_array($value)) {
            $value = [$value];
        }
        $this->result->{$methodName}($value);

        return true;
    }

    /**
     * @param DateTimeInterface|int|mixed|string $value
     */
    private function getDateFrom($value): ?DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        if (is_numeric($value)) {
            switch (strlen((string) $value)) {
                case 2:
                    $format = 'y-m-d h:i:s';
                    $value .= '-01-01 00:00:00';

                    break;
                case 4:
                    $format = 'Y-m-d h:i:s';
                    $value .= '-01-01 00:00:00';

                    break;
                default:
                    $format = 'U';
            }

            return DateTime::createFromFormat($format, (string) $value) ?: null;
        }

        return $this->guessDateFrom($value);
    }

    /**
     * @param int|string $value
     */
    private function guessDateFrom($value): ?DateTimeInterface
    {
        try {
            $parsed = false;
            foreach ([
                DateTimeInterface::ATOM,
                'Y-m-d', // ISO 8601
                'd.m.Y',
                'j.n.Y',
                'Y-m',
                'm-Y',
            ] as $format) {
                $parsed = DateTime::createFromFormat($format, (string) $value);
                if ($parsed instanceof DateTimeInterface) {
                    break;
                }
            }

            return ($parsed instanceof DateTimeInterface) ? $parsed : new DateTime((string) $value);
        } catch (Exception $exception) {
            return null;
        }
    }

    /**
     * @param DateTimeInterface|mixed $value
     */
    private function handleDate(string $field, $value): bool
    {
        if (!in_array($field, self::DATE_FIELDS, true)) {
            return false;
        }

        $methodName = 'set'.ucfirst($field);

        $value = $this->getDateFrom($value);

        if (($value instanceof DateTimeInterface)) {
            $this->result->{$methodName}($value);

            return true;
        }

        return false;
    }

    /**
     * @param array|mixed $value
     *
     * @return array|mixed|string
     */
    private function handleArrayData(string $field, $value)
    {
        if (!is_array($value)) {
            return $value;
        }

        if (in_array($field, self::STRING_FIELDS, true)) {
            return implode(', ', $value);
        }

        if (1 === count($value) && in_array($field, self::INT_FIELDS, true)) {
            return reset($value);
        }

        return $value;
    }

    /**
     * @param array<int>|float|int|string $value
     */
    private function handleInt(string $field, $value): bool
    {
        $methodName = 'set'.ucfirst($field);

        if (is_array($value) && 1 === count($value)) {
            $value = reset($value);
        }

        if (is_numeric($value) && in_array($field, self::INT_FIELDS, true)) {
            $this->result->{$methodName}((int) $value);

            return true;
        }

        return false;
    }

    /**
     * @param array<string>|string $value
     */
    private function handleString(string $field, $value): bool
    {
        $methodName = 'set'.ucfirst($field);

        if (in_array($field, self::STRING_FIELDS, true)) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $this->result->{$methodName}((string) $value);

            return true;
        }

        return false;
    }

    /**
     * @param null|mixed $value
     */
    private function appendToAdditional(string $field, $value): void
    {
        $additional = $this->result->getAdditional();
        $current = $additional[$field] ?? [];
        if (!is_array($value)) {
            $value = [$value];
        }
        $current = array_unique(array_merge($current, $value));
        $additional[$field] = $current;
        $this->result->setAdditional($additional);
    }

    /**
     * @param null|mixed $value
     */
    private function handleSpecialArray(string $field, $value): bool
    {
        if (!in_array($field, self::SPECIAL_ARRAY_FIELDS, true)) {
            return false;
        }
        $methodName = 'set'.ucfirst($field);
        $getter = 'get'.ucfirst($field);

        $previous = $this->result->{$getter}();
        $previous[] = $value;
        $this->result->{$methodName}(array_unique($previous));

        return true;
    }
}
