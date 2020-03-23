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

use DateTimeInterface;
use MacFJA\BookRetriever\SearchResultInterface;

class SearchResult implements SearchResultInterface
{
    /** @var null|string */
    private $isbn;

    /** @var null|string */
    private $title;

    /** @var string[] */
    private $authors = [];

    /** @var null|int */
    private $pages;

    /** @var null|string */
    private $series;

    /** @var string[] */
    private $illustrators = [];

    /** @var string[] */
    private $translators = [];

    /** @var string[] */
    private $genres = [];

    /** @var null|DateTimeInterface */
    private $publicationDate;

    /** @var null|string */
    private $format;

    /** @var null|string */
    private $dimension;

    /** @var string[] */
    private $keywords = [];

    /** @var null|string */
    private $cover;

    /** @var array */
    private $additional = [];

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    /**
     * @suppress PhanUnreferencedPublicMethod
     */
    public function setIsbn(?string $isbn): void
    {
        $this->isbn = $isbn;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @suppress PhanUnreferencedPublicMethod
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string[]
     */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    /**
     * @param string[] $authors
     * @suppress PhanUnreferencedPublicMethod
     */
    public function setAuthors(array $authors): void
    {
        $this->authors = $authors;
    }

    public function getPages(): ?int
    {
        return $this->pages;
    }

    /**
     * @suppress PhanUnreferencedPublicMethod
     */
    public function setPages(?int $pages): void
    {
        $this->pages = $pages;
    }

    public function getSeries(): ?string
    {
        return $this->series;
    }

    /**
     * @suppress PhanUnreferencedPublicMethod
     */
    public function setSeries(?string $series): void
    {
        $this->series = $series;
    }

    /**
     * @return string[]
     */
    public function getIllustrators(): array
    {
        return $this->illustrators;
    }

    /**
     * @param string[] $illustrators
     * @suppress PhanUnreferencedPublicMethod
     */
    public function setIllustrators(array $illustrators): void
    {
        $this->illustrators = $illustrators;
    }

    /**
     * @return string[]
     */
    public function getTranslators(): array
    {
        return $this->translators;
    }

    /**
     * @param string[] $translators
     * @suppress PhanUnreferencedPublicMethod
     */
    public function setTranslators(array $translators): void
    {
        $this->translators = $translators;
    }

    /**
     * @return string[]
     */
    public function getGenres(): array
    {
        return $this->genres;
    }

    /**
     * @param string[] $genres
     * @suppress PhanUnreferencedPublicMethod
     */
    public function setGenres(array $genres): void
    {
        $this->genres = $genres;
    }

    public function getPublicationDate(): ?DateTimeInterface
    {
        return $this->publicationDate;
    }

    /**
     * @suppress PhanUnreferencedPublicMethod
     */
    public function setPublicationDate(DateTimeInterface $publicationDate): void
    {
        $this->publicationDate = $publicationDate;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    /**
     * @suppress PhanUnreferencedPublicMethod
     */
    public function setFormat(?string $format): void
    {
        $this->format = $format;
    }

    public function getDimension(): ?string
    {
        return $this->dimension;
    }

    /**
     * @suppress PhanUnreferencedPublicMethod
     */
    public function setDimension(?string $dimension): void
    {
        $this->dimension = $dimension;
    }

    /**
     * @return string[]
     */
    public function getKeywords(): array
    {
        return $this->keywords;
    }

    /**
     * @param string[] $keywords
     * @suppress PhanUnreferencedPublicMethod
     */
    public function setKeywords(array $keywords): void
    {
        $this->keywords = $keywords;
    }

    public function getCover(): ?string
    {
        return $this->cover;
    }

    /**
     * @suppress PhanUnreferencedPublicMethod
     */
    public function setCover(?string $cover): void
    {
        $this->cover = $cover;
    }

    public function getAdditional(): array
    {
        return $this->additional;
    }

    public function setAdditional(array $additional): void
    {
        $this->additional = $additional;
    }
}
