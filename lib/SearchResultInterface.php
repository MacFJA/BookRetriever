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

use DateTimeInterface;

/**
 * Contract of what a book search result must be.
 */
interface SearchResultInterface
{
    public function getIsbn(): ?string;

    public function getTitle(): ?string;

    public function getPages(): ?int;

    public function getSeries(): ?string;

    public function getAuthors(): array;

    public function getIllustrators(): array;

    public function getTranslators(): array;

    public function getGenres(): array;

    public function getPublicationDate(): ?DateTimeInterface;

    public function getFormat(): ?string;

    public function getDimension(): ?string;

    public function getKeywords(): array;

    public function getCover(): ?string;

    /**
     * Any additional data.
     * Should be an array with the key the data name, and the value an array of data.
     *
     * @return array<string,array>
     */
    public function getAdditional(): array;
}
