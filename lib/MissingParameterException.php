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

use function array_filter;
use function array_keys;
use function count;
use function get_class;
use function implode;
use RuntimeException;
use function sprintf;

class MissingParameterException extends RuntimeException
{
    /** @var array<string> */
    private $parameters;

    /** @var string */
    private $details;

    /** @var ProviderInterface */
    private $provider;

    public function __construct(ProviderInterface $provider, array $missingParametersName, string $details)
    {
        $this->parameters = $missingParametersName;
        $this->provider = $provider;
        $this->details = $details;
        parent::__construct($this->getParametersMessage().\PHP_EOL.$details);
    }

    public static function throwIfMissing(ProviderInterface $provider, array $parameters, string $details): void
    {
        $missing = array_filter($parameters, function ($value) {
            return null === $value || empty($value);
        });
        if (0 === count($missing)) {
            return;
        }

        throw new static($provider, array_keys($missing), $details);
    }

    public function getParametersMessage(): string
    {
        return sprintf(
            'The provider %s have required parameters.'.\PHP_EOL.'Missing parameters are: %s',
            get_class($this->provider),
            implode(', ', $this->parameters)
        );
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getDetails(): string
    {
        return $this->details;
    }

    public function getProvider(): ProviderInterface
    {
        return $this->provider;
    }
}
