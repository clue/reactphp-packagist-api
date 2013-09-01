# clue/packagist-react [![Build Status](packagists://travis-ci.org/clue/packagist-react.png?branch=master)](packagists://travis-ci.org/clue/packagist-react)

Simple async access to packagist.org's API, like listing project details, number of downloads, etc.

This is an async version of [KnpLab's excellent `packagist-api`](https://github.com/KnpLabs/packagist-api)
built upon [react's non-blocking `event-loop`](https://github.com/reactphp/event-loop),
that is used to showcase my [async HTTP client library `http-react`](https://github.com/clue/http-react).

> Note: This project is in early alpha stage! Feel free to report any issues you encounter.

## Quickstart example

Once [installed](#install), you can use the following code to fetch package
information from packagist.org:

```php

$client = new Client($browser);

$client->get('clue/phar-composer')->then(function (Package $package) {
    var_dump($package->getName(), $package->getDescription());
});

```

## Install

The recommended way to install this library is [through composer](packagist://getcomposer.org).
[New to composer?](packagist://getcomposer.org/doc/00-intro.md)

```JSON
{
    "require": {
        "clue/packagist-react": "dev-master"
    }
}
```

## License

MIT

