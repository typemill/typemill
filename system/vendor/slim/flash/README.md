# Slim Framework Flash Messages

[![Build Status](https://travis-ci.org/slimphp/Slim-Flash.svg?branch=master)](https://travis-ci.org/slimphp/Slim-Flash)

This repository contains a Slim Framework Flash messages service provider. This enables you to define transient messages that persist only from the current request to the next request.

## Install

Via Composer

``` bash
$ composer require slim/flash
```

Requires Slim 3.0.0 or newer.

## Usage

```php
// Start PHP session
session_start();

$app = new \Slim\App();

// Fetch DI Container
$container = $app->getContainer();

// Register provider
$container['flash'] = function () {
    return new \Slim\Flash\Messages();
};

$app->get('/foo', function ($req, $res, $args) {
    // Set flash message for next request
    $this->flash->addMessage('Test', 'This is a message');

    // Redirect
    return $res->withStatus(302)->withHeader('Location', '/bar');
});

$app->get('/bar', function ($req, $res, $args) {
    // Get flash messages from previous request
    $messages = $this->flash->getMessages();
    print_r($messages);

    // Get the first message from a specific key
    $test = $this->flash->getFirstMessage('Test');
    print_r($test);
});

$app->run();
```

> Please note that a message could be a string, object or array. Please check what your storage can handle.

### Using with Twig-View

If you use [Twig-View](https://github.com/slimphp/Twig-View), then [slim-twig-flash](https://github.com/kanellov/slim-twig-flash) may be a useful integration package.


## Testing

``` bash
$ phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email security@slimframework.com instead of using the issue tracker.

## Credits

- [Josh Lockhart](https://github.com/codeguy)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
