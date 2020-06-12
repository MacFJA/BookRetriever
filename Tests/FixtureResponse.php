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

namespace MacFJA\BookRetriever\Tests;

use function array_keys;
use function array_map;
use function array_values;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function gzcompress;
use function gzuncompress;
use function implode;
use function preg_match;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use function sprintf;
use function trim;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-suppress MissingConstructor
 */
class FixtureResponse
{
    protected const HTTP_RESPONSE_PATTERN = '/^(?P<httpversion>[\w\/\.]+)[ ]+(?P<statuscode>\d{3})[ ]+(?P<statustext>[^\n]*)\r\n(?<headers>(?:[^\n]+\r\n)+)\r\n(?P<body>.+)$/sD';

    /** @var ResponseFactoryInterface */
    protected $responseFactory;

    /** @var StreamFactoryInterface */
    protected $streamFactory;

    public function setResponseFactory(ResponseFactoryInterface $responseFactory): FixtureResponse
    {
        $this->responseFactory = $responseFactory;

        return $this;
    }

    public function setStreamFactory(StreamFactoryInterface $streamFactory): FixtureResponse
    {
        $this->streamFactory = $streamFactory;

        return $this;
    }

    public function createFromRaw(string $raw): ResponseInterface
    {
        preg_match(self::HTTP_RESPONSE_PATTERN, $raw, $matches);

        $body = $this->streamFactory->createStream($matches['body']);
        $body->seek(0);
        $response = $this->responseFactory
            ->createResponse((int) $matches['statuscode'], $matches['statustext'])
            ->withBody($body)
            ->withProtocolVersion($matches['httpversion']);

        $headers = explode("\r\n", $matches['headers']);
        foreach ($headers as $header) {
            list($name, $value) = explode(':', $header, 2);
            if (empty($value)) {
                continue;
            }
            $response = $response->withAddedHeader(trim($name), trim($value));
        }

        return $response;
    }

    public function saveToRaw(ResponseInterface $response): string
    {
        $headers = $response->getHeaders();
        $headers = array_map(function ($headerName, $headerValues) {
            $line = array_map(function ($header) use ($headerName) {
                return $headerName.': '.$header;
            }, $headerValues);

            return implode("\r\n", $line);
        }, array_keys($headers), array_values($headers));

        return sprintf(
            '%s %d %s'."\r\n".'%s'."\r\n\r\n".'%s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            implode("\r\n", $headers),
            $response->getBody()->getContents()
        );
    }

    public function dumpCompressed(string $filename, ResponseInterface $response): void
    {
        file_put_contents($filename, gzcompress($this->saveToRaw($response)));
    }

    public function readCompressed(string $filename): ResponseInterface
    {
        return $this->createFromRaw(gzuncompress(file_get_contents($filename)));
    }
}
