# clue/reactphp-packagist-api

[![CI status](https://github.com/clue/reactphp-packagist-api/workflows/CI/badge.svg)](https://github.com/clue/reactphp-packagist-api/actions)

Simple async access to packagist.org's API, like listing project details, number of downloads etc.,
built on top of [ReactPHP](https://reactphp.org/).

This is an async version of [KnpLab's excellent `packagist-api`](https://github.com/KnpLabs/packagist-api),
but built upon [ReactPHP's non-blocking `event-loop`](https://github.com/reactphp/event-loop).
It uses the [async HTTP client library `clue/reactphp-buzz`](https://github.com/clue/reactphp-buzz) to process
any number of requests in parallel.
In a nutshell, it allows you to issue multiple requests to the packagist API in parallel and process them out of order
whenever their results arrive - while trying to hide all the nifty details of async processing.
On top of that it provides a very easy to use API, very much similar to the original `packagist-api`,
enriched with the comfort of [ReactPHP's Promises](https://github.com/reactphp/promise).

**Table of Contents**

* [Quickstart example](#quickstart-example)
* [Usage](#usage)
  * [Client](#client)
    * [Promises](#promises)
    * [Cancellation](#cancellation)
    * [Timeouts](#timeouts)
    * [search()](#search)
    * [get()](#get)
    * [all()](#all)
  * [Package](#package)
    * [getName()](#getname)
    * [getDescription()](#getdescription)
* [Install](#install)
* [Tests](#tests)
* [License](#license)

## Quickstart example

Once [installed](#install), you can use the following code to fetch package
information from packagist.org:

```php
$loop = React\EventLoop\Factory::create();
$browser = new Clue\React\Buzz\Browser($loop);
$client = new Client($browser);

$client->get('clue/phar-composer')->then(function (Package $package) {
    var_dump($package->getName(), $package->getDescription());
});

$loop->run();
```

See also the [examples](examples).

## Usage

### Client

The `Client` is responsible for assembling and sending HTTP requests to the remote Packagist API.
It requires a [`Browser`](https://github.com/clue/reactphp-buzz#browser) object
bound to the main [`EventLoop`](https://github.com/reactphp/event-loop#usage)
in order to handle async requests:

```php
$loop = React\EventLoop\Factory::create();
$browser = new Clue\React\Buzz\Browser($loop);

$client = new Client($browser);
```

If you need custom connector settings (DNS resolution, TLS parameters, timeouts,
proxy servers etc.), you can explicitly pass a custom instance of the
[`ConnectorInterface`](https://github.com/reactphp/socket#connectorinterface)
to the [`Browser`](https://github.com/clue/reactphp-buzz#browser) instance:

```php
$connector = new \React\Socket\Connector($loop, array(
    'dns' => '127.0.0.1',
    'tcp' => array(
        'bindto' => '192.168.10.1:0'
    ),
    'tls' => array(
        'verify_peer' => false,
        'verify_peer_name' => false
    )
));

$browser = new Browser($loop, $connector);
$client = new Client($browser);
```

#### Promises

All public methods on the `Client` resemble the API provided by [KnpLab's `packagist-api`](https://github.com/KnpLabs/packagist-api),
except for an async shift in their return values:
Sending requests is async (non-blocking), so you can actually send multiple requests in parallel.
Packagist will respond to each request with a response message, the order is not guaranteed.
Sending requests uses a [Promise](https://github.com/reactphp/promise)-based interface that makes it easy to react to when a request is fulfilled (i.e. either successfully resolved or rejected with an error).

```php
$client->get('clue/graph-composer')->then(
    function ($result) {
        // result received for get() function
    },
    function (Exception $e) {
        // an error occured while executing the request
    }
});
```

#### Cancellation

The returned Promise is implemented in such a way that it can be cancelled
when it is still pending.
Cancelling a pending promise will reject its value with an Exception and
clean up any underlying resources.

```php
$promise = $client->get('clue/graph-composer');

$loop->addTimer(2.0, function () use ($promise) {
    $promise->cancel();
});
```

#### Timeouts

This library uses a very efficient HTTP implementation, so most API requests
should usually be completed in mere milliseconds. However, when sending API
requests over an unreliable network (the internet), there are a number of things
that can go wrong and may cause the request to fail after a time. As such,
timeouts are handled by the underlying HTTP library and this library respects
PHP's `default_socket_timeout` setting (default 60s) as a timeout for sending the
outgoing API request and waiting for a successful response and will otherwise
cancel the pending request and reject its value with an Exception.

Note that this timeout value covers creating the underlying transport connection,
sending the API request, waiting for the Packagist service to process the request
and receiving the full API response. To pass a custom timeout value, you can
assign the underlying [`timeout` option](https://github.com/clue/reactphp-buzz#timeouts)
like this:

```php
$browser = new Browser($loop);
$browser = $browser->withOptions(array(
    'timeout' => 10.0
));

$client = new Client($browser);

$client->get('clue/graph-composer')->then(function ($result) {
    // result received within 10 seconds maximum
    var_dump($result);
});
```

Similarly, you can use a negative timeout value to not apply a timeout at all
or use a `null` value to restore the default handling. Note that the underlying
connection may still impose a different timeout value. See also the underlying
[`timeout` option](https://github.com/clue/reactphp-buzz#timeouts) for more details.

#### search()

The `search(string $query, array $filters = array()): PromiseInterface<Package[],Exception>` method can be used to
search packages matching the given query string and optionally matching the given filter parameter.

It resolves with an array containing zero or more [`Package`](#package) objects
on success or rejects with an `Exception` on error.

```php
$client->search('packagist')->then(function (array $packages) {
    foreach ($packages as $package) {
        echo $package->getName() . PHP_EOL;
    }
});
```

Note that this method follows Packagist's paginated search results which
may contain a large number of matches depending on your search.
Accordingly, this method sends one API request for each page which may
take a while for the whole search to be completed. It is not uncommon to
take around 5-10 seconds to fetch search results for 1000 matches.

#### get()

The `get(string $name): PromiseInterface<Package,Exception>` method can be used to
get package details for the given package name.

It resolves with a single [`Package`](#package) object
on success or rejects with an `Exception` on error.

```php
$client->get('clue/packagist-api-react')->then(function (Package $package) {
    echo $package->getDescription();
});
```

#### all()

The `all(array $filters = array()): PromiseInterface<string[],Exception>` method an be used to
list all package names, optionally matching the given filter parameter.

It resolves with an array of package names
on success or rejects with an `Exception` on error.

```php
$client->all(array('vendor' => 'clue'))->then(function (array $names) {
    // array containing (among others) "clue/packagist-api-react"
});
```

### Package

The `Package` class represents information about a given composer package.
This class is part of the underlying [KnpLab/packagist-api](https://github.com/KnpLabs/packagist-api),
its full name is actually `Packagist\Api\Result\Package`.

See its class outline for all available methods.

#### getName()

The `getName()` method can be used to get the package name.

#### getDescription()

The `getDescription()` method can be used to the package description.

## Install

The recommended way to install this library is [through Composer](https://getcomposer.org).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

This project follows [SemVer](https://semver.org/).
This will install the latest supported version:

```bash
$ composer require clue/packagist-api-react:^1.3
```

See also the [CHANGELOG](CHANGELOG.md) for details about version upgrades.

This project aims to run on any platform and thus does not require any PHP
extensions and supports running on legacy PHP 5.3 through current PHP 7+ and
HHVM.
It's *highly recommended to use PHP 7+* for this project.

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org):

```bash
$ composer install
```

To run the test suite, go to the project root and run:

```bash
$ php vendor/bin/phpunit
```

## License

This project is released under the permissive [MIT license](LICENSE).

> Did you know that I offer custom development services and issuing invoices for
  sponsorships of releases and for contributions? Contact me (@clue) for details.
