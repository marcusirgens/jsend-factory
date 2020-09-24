# JSendFactory

JSendFactory is a PHP library for creating [JSend-compliant] [PSR-7] requests.

## Installation

Use the package manager [composer](https://getcomposer.org/download/) to install
the package.

```bash
composer require marcusirgens/jsend-factory
```

You will also need some [PSR-17] implementation. You can find [a list of packages
providing this at packagist.org](https://packagist.org/providers/psr/http-factory-implementation).
If you're unsure, go for [nyholm/psr7].

## Usage

```php
$responseFactory = new Psr17ResponseFactory();
$streamFactory  = new Psr17StreamFactory();

$jsendFactory = \MarcusIrgens\JSendFactory\JSendFactory(
    $responseFactory,
    $streamFactory
);

$successResponse = $jsendFactory->getSuccess(["message" => "hello"]);
```

## Contributing
Pull requests are welcome. For major changes, please open an issue first to 
discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)

[JSend-compliant]: https://github.com/omniti-labs/jsend
[PSR-7]: https://www.php-fig.org/psr/psr-7/
[PSR-17]: https://www.php-fig.org/psr/psr-17/
[nyholm/psr7]: https://github.com/Nyholm/psr7