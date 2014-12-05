# clue/packagist-api-react [![Build Status](https://travis-ci.org/clue/php-packagist-api-react.svg?branch=master)](https://travis-ci.org/clue/php-packagist-api-react)

Simple async access to packagist.org's API, like listing project details, number of downloads, etc.

This is an async version of [KnpLab's excellent `packagist-api`](https://github.com/KnpLabs/packagist-api),
but built upon [React PHP's non-blocking `event-loop`](https://github.com/reactphp/event-loop).
It uses the [async HTTP client library `buzz-react`](https://github.com/clue/php-buzz-react) to process
any number of requests in parallel.

In a nutshell, it allows you to issue multiple requests to the packagist API in parallel and process them out of order
whenever their results arrive - while trying to hide all the nifty details of async processing.
On top of that it provides a very easy to use API, very much similar to the original `packagist-api`,
enriched with the comfort of [React PHP's Promises/A](https://github.com/reactphp/promise).


> Note: This project is in beta stage! Feel free to report any issues you encounter.

## Quickstart example

Once [installed](#install), you can use the following code to fetch package
information from packagist.org:

```php
$client = new Client($browser);

$client->get('clue/phar-composer')->then(function (Package $package) {
    var_dump($package->getName(), $package->getDescription());
});

```

See also the [examples](examples).

## Install

The recommended way to install this library is [through composer](https://getcomposer.org).
[New to composer?](https://getcomposer.org/doc/00-intro.md)

```JSON
{
    "require": {
        "clue/packagist-api-react": "~0.1.0"
    }
}
```

## Usage

### Client

The `Client` is responsible for assembling and sending HTTP requests to the remote Packagist API.
It requires a `Browser` object bound to the main `EventLoop` in order to handle async requests:

```php
$loop = React\EventLoop\Factory::create();
$browser = Clue\React\Buzz\Browser($loop);

$client = new Client($browser);
```

All public methods on the `Client` resemble the API provided by [KnpLab's `packagist-api`](https://github.com/KnpLabs/packagist-api),
except for an async shift in their return values.
Sending requests is async (non-blocking), so you can actually send multiple requests in parallel.
Packagist will respond to each request with a response message, the order is not guaranteed.
Sending requests uses a [Promise](https://github.com/reactphp/promise)-based interface that makes it easy to react to when a request is fulfilled (i.e. either successfully resolved or rejected with an error).

The `search($query, $filters = array())` method can be used to search packages matching the given query string and optionally matching the given filter parameter.
It resolves with an array containing zero or more `Package` objects.

```php
$client->search('packagist')->then(function ($results) {
    foreach ($results as $result) {
        echo $package->getName() . PHP_EOL;
    }
});
```

The `get($name)` method can be used to get package details for the given package name.
It resolves with a single `Package` object.

```php
$client->get('clue/packagist-api-react')->then(function (Package $package) {
    echo $package->getDescription();
});
```

The `all($filters = array())` method an be used to list all package names, optionally matching the given filter parameter.
It resolves with an array of package names.

```php
$client->all(array('vendor' => 'clue'))->then(function ($list) {
    // array containing (among others) "clue/packagist-api-react"
});
```

### Package

The `Package` class represents information about a given composer package.
This class is part of the underlying [KnpLab/packagist-api](https://github.com/KnpLabs/packagist-api),
its full name is actually `Packagist\Api\Result\Package`.

The `getName()` method can be used to get the package name.

The `getDescription()` method can be used to the package description.

See the class outline for all available methods.

## License

MIT

