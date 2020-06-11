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

use function array_merge;
use function array_reduce;
use function array_unique;
use function assert;
use FilterIterator;
use Iterator;
// @phan-suppress-next-line PhanUnreferencedUseNormal
use IteratorIterator;
use Traversable;

/**
 * Class Pool.
 *
 * @suppress PhanUnreferencedClass
 */
class Pool implements ProviderInterface
{
    /** @var iterable<ProviderInterface> */
    protected $providers;

    /** @var ProviderConfigurationInterface */
    protected $configuration;

    /**
     * Pool constructor.
     *
     * @param iterable<ProviderInterface> $providers
     */
    public function __construct(iterable $providers, ProviderConfigurationInterface $configuration)
    {
        $this->providers = $providers;
        $this->configuration = $configuration;
    }

    public function search(array $criteria): array
    {
        $resultGroup = [];
        foreach ($this->getActiveProviders() as $provider) {
            $resultGroup[$provider->getCode()] = array_unique($provider->search($criteria), \SORT_REGULAR);
        }

        return array_reduce($resultGroup, function (array $carry, array $item): array {
            return array_merge($carry, $item);
        }, []);
    }

    public function searchIsbn(string $isbn): array
    {
        $resultGroup = [];
        foreach ($this->getActiveProviders() as $provider) {
            $resultGroup[$provider->getCode()] = array_unique($provider->searchIsbn($isbn), \SORT_REGULAR);
        }

        return array_reduce($resultGroup, function (array $carry, array $item): array {
            return array_merge($carry, $item);
        }, []);
    }

    public function getCode(): string
    {
        return '__pool__';
    }

    public static function getLabel(): string
    {
        return '__pool__';
    }

    public function getSearchableField(): array
    {
        $fields = [];
        /** @var ProviderInterface $provider */
        foreach ($this->getActiveProviders() as $provider) {
            $fields = array_merge($fields, $provider->getSearchableField());
        }

        return array_unique($fields);
    }

    /**
     * @return iterable<ProviderInterface>
     * @psalm-suppress RedundantConditionGivenDocblockType
     */
    protected function getActiveProviders(): iterable
    {
        /** @var Traversable<ProviderInterface> $providers */
        $providers = $this->providers;
        assert($providers instanceof Traversable);

        return new class(new IteratorIterator($providers), $this->configuration) extends FilterIterator {
            /** @var ProviderConfigurationInterface */
            protected $configuration;

            public function __construct(Iterator $iterator, ProviderConfigurationInterface $configuration)
            {
                parent::__construct($iterator);
                $this->configuration = $configuration;
            }

            public function accept()
            {
                /** @var ProviderInterface $provider */
                $provider = $this->current();

                return $this->configuration->isActive($provider);
            }
        };
    }
}
