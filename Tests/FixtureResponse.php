<?php


namespace MacFJA\BookRetriever\Tests;


use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class FixtureResponse
{
    /** @var ResponseFactoryInterface */
    protected $responseFactory;
    /** @var StreamFactoryInterface */
    protected $streamFactory;
    protected const HTTP_RESPONSE_PATTERN = '/^(?P<httpversion>[\w\/\.]+)[ ]+(?P<statuscode>\d{3})[ ]+(?P<statustext>[^\n]*)\r\n(?<headers>(?:[^\n]+\r\n)+)\r\n(?P<body>.+)$/sD';

    /**
     * @param ResponseFactoryInterface $responseFactory
     * @return FixtureResponse
     */
    public function setResponseFactory(ResponseFactoryInterface $responseFactory): FixtureResponse
    {
        $this->responseFactory = $responseFactory;
        return $this;
    }

    /**
     * @param StreamFactoryInterface $streamFactory
     * @return FixtureResponse
     */
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
            ->withProtocolVersion($matches['httpversion'])
        ;

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
                return $headerName . ': '.$header;
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