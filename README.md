# Book Retriever

Library to retrieve data about books

## Installation

```shell script
composer require macfja/book-retriever
```

## Usage

### Simple provider

To get information of a book on a specific provider:

```php
$htmlGetter = new \MacFJA\BookRetriever\Helper\HtmlGetter();
$isbnTool = new \Isbn\Isbn();

$antoineOnline = new \MacFJA\BookRetriever\Provider\AntoineOnline($htmlGetter, $isbnTool);
$books = $antoineOnline->searchIsbn('9782253006329');
// $books contains a list of \MacFJA\BookRetriever\SearchResultInterface
```

### Configurable provider

To get information of a book with a configurable provider:

```php
$providerConfiguration = ...; // A class that implement \MacFJA\BookRetriever\ProviderConfigurationInterface
$configurator = new \MacFJA\BookRetriever\ProviderConfigurator($providerConfiguration);
$amazon = new \MacFJA\BookRetriever\Provider\Amazon();
$configurator->configure($amazon);
$books = $amazon->searchIsbn('9782253006329');
// $books contains a list of \MacFJA\BookRetriever\SearchResultInterface
```

### Multiple providers

Using the `Pool` provider to request several providers:

```php
$providerConfiguration = ...; // A class that implement \MacFJA\BookRetriever\ProviderConfigurationInterface
$configurator = new \MacFJA\BookRetriever\ProviderConfigurator($providerConfiguration);

$htmlGetter = new \MacFJA\BookRetriever\Helper\HtmlGetter();
$isbn = new \Isbn\Isbn();
$opds = new \MacFJA\BookRetriever\Helper\OPDSParser();
$sru = new \MacFJA\BookRetriever\Helper\SRUParser();

$providers = [
    new \MacFJA\BookRetriever\Provider\AbeBooks($htmlGetter),
    new \MacFJA\BookRetriever\Provider\Amazon(),
    new \MacFJA\BookRetriever\Provider\AntoineOnline($htmlGetter, $isbn),
    new \MacFJA\BookRetriever\Provider\ArchiveOrg($opds),
    new \MacFJA\BookRetriever\Provider\COPAC($sru),
    new \MacFJA\BookRetriever\Provider\DigitEyes(),
    new \MacFJA\BookRetriever\Provider\Ebay()
];
array_walk($providers, [$configurator, 'configure']);

$pool = new \MacFJA\BookRetriever\Pool($providers, $providerConfiguration);

$books = $pool->searchIsbn('9782253006329');
// $books contains a list of \MacFJA\BookRetriever\SearchResultInterface
```

If you use an dependency injection library, lots of code can be remove. (see below for a Symfony example)

### With Symfony (and Doctrine)

An example of integration in Symfony with configuration stored in database with Doctrine as ORM.

`config/services.yaml`
```yaml
services:
    _instanceof:
        # services whose classes are instances of ProviderInterface will be tagged automatically
        MacFJA\BookRetriever\ProviderInterface:
            tags: ['app.provider']
    MacFJA\BookRetriever\:
        resource: '../vendor/macfja/book-retriever/lib/'
    MacFJA\BookRetriever\Pool:
        arguments:
            $providers: !tagged_iterator app.provider
    MacFJA\BookRetriever\ProviderConfigurationInterface: '@App\Repository\ProviderConfigurationRepository'
```

`src/Entity/ProviderConfiguration.php`
```php
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
/** @ORM\Entity(repositoryClass="App\Repository\ProviderConfigurationRepository") */
class ProviderConfiguration
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;
    /** @ORM\Column(type="string", length=50) */
    private $provider;
    /** @ORM\Column(type="boolean") */
    private $active;
    /** @ORM\Column(type="json") */
    private $parameters = [];

    // All Getters/Setters
    // Removed in this example for readability
}
```

`src/Repository/ProviderConfigurationRepository.php`
```php
<?php
namespace App\Repository;
use App\Entity\ProviderConfiguration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use MacFJA\BookRetriever\ProviderConfigurationInterface;
use MacFJA\BookRetriever\ProviderInterface;

class ProviderConfigurationRepository extends ServiceEntityRepository implements ProviderConfigurationInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProviderConfiguration::class);
    }

    public function getParameters(ProviderInterface $provider): array
    {
        $configuration = $this->findOneBy(['provider' => $provider->getCode()]);
        
        return $configuration !== null ? $configuration->getParameters() : [];
    }

    public function isActive(ProviderInterface $provider): bool
    {
        $configuration = $this->findOneBy(['provider' => $provider->getCode()]);
        // not active by default
        return $configuration !== null ? $configuration->getActive() : false;
    }
}
```

`src/Controller/SomeController.php`
```php
<?php
namespace App\Controller;
use MacFJA\BookRetriever\Pool;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class SomeController extends AbstractController
{
    /** @Route("/test") */
    public function index(Pool $pool)
    {
        return new JsonResponse($pool->searchIsbn('9782253006329'));
    }
}
```

## Contributing

You can contribute to the library.
To do so, you have Github issues to:
 - ask your question
 - notify any change in the providers
 - suggest new provider
 - request any change (typo, bad code, etc.)
 - and much more...

You also have PR to:
 - add a new provider
 - suggest a correction
 - and much more... 

### Local installation

First clone the project (either this repository, or your fork),
next run:
```shell script
make install # Install project vendor
make all # Run QA tools + tests suites + generate docs
```

### Validate your code

When you done writing your code run the following command check if the quality meet defined rule and to format it:
```shell script
make analyze # Run QA tools + tests suites
```
If you add unit tests you run the following to do the same on test suite code:
```shell script
make analyze-tests # Run QA tools on tests suites
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.