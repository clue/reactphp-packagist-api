<?php

namespace Clue\React\Packagist\Api;

use Clue\React\Buzz\Browser;
use Packagist\Api\Result\Factory;
use Packagist\Api\Result\Package;
use Psr\Http\Message\ResponseInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Rize\UriTemplate;

class Client
{
    private $http;
    private $resultFactory;
    private $uri;

    public function __construct(Browser $http, Factory $resultFactory = null, UriTemplate $uri = null)
    {
        $this->http = $http->withBase('https://packagist.org/');

        if (null === $resultFactory) {
            $resultFactory = new Factory();
        }

        if (null === $uri) {
            $uri = new UriTemplate();
        }

        $this->resultFactory = $resultFactory;
        $this->uri = $uri;
    }

    /**
     * Search packages matching the given query string and optionally matching the given filter parameter.
     *
     * It resolves with an array containing zero or more [`Package`](#package) objects
     * on success or rejects with an `Exception` on error.
     *
     * ```php
     * $client->search('packagist')->then(function (array $packages) {
     *     foreach ($packages as $package) {
     *         echo $package->getName() . PHP_EOL;
     *     }
     * });
     * ```
     *
     * Note that this method follows Packagist's paginated search results which
     * may contain a large number of matches depending on your search.
     * Accordingly, this method sends one API request for each page which may
     * take a while for the whole search to be completed. It is not uncommon to
     * take around 5-10 seconds to fetch search results for 1000 matches.
     *
     * @param string $query
     * @param array  $filters
     * @return PromiseInterface<Package[],\Exception>
     */
    public function search($query, array $filters = array())
    {
        $filters['q'] = $query;

        $url = $this->uri->expand(
            '/search.json{?filters*}',
            array(
                'filters' => $filters
            )
        );

        $results = array();
        $that = $this;

        $pending = null;
        $deferred = new Deferred(function () use (&$pending) {
            $pending->cancel();
        });

        $fetch = function ($url) use (&$results, $that, &$fetch, $deferred, &$pending) {
            $pending = $that->request($url)->then(function (ResponseInterface $response) use (&$results, $that, $fetch, $deferred) {
                $parsed = $that->parse((string)$response->getBody());
                $results = array_merge($results, $that->create($parsed));

                if (isset($parsed['next'])) {
                    $fetch($parsed['next']);
                } else {
                    $deferred->resolve($results);
                }
            }, function ($e) use ($deferred) {
                $deferred->reject($e);
            });
        };
        $fetch($url);

        return $deferred->promise();
    }

    /**
     * Get package details for the given package name.
     *
     * It resolves with a single [`Package`](#package) object
     * on success or rejects with an `Exception` on error.
     *
     * ```php
     * $client->get('clue/packagist-api-react')->then(function (Package $package) {
     *     echo $package->getDescription();
     * });
     * ```
     *
     * @param string $package
     * @return PromiseInterface<Package,\Exception>
     */
    public function get($package)
    {
        return $this->respond(
            $this->uri->expand(
                '/packages/{package}.json',
                array(
                    'package' => $package
                )
            )
        );
    }

    /**
     * List all package names, optionally matching the given filter parameter.
     *
     * It resolves with an array of package names
     * on success or rejects with an `Exception` on error.
     *
     * ```php
     * $client->all(array('vendor' => 'clue'))->then(function (array $names) {
     *     // array containing (among others) "clue/packagist-api-react"
     * });
     * ```
     *
     * @param array $filters
     * @return PromiseInterface<string[],\Exception>
     */
    public function all(array $filters = array())
    {
        return $this->respond(
            $this->uri->expand(
                '/packages/list.json{?filters*}',
                array(
                    'filters' => $filters
                )
            )
        );
    }

    protected function respond($url)
    {
        $response = $this->request($url);
        $that     = $this;

        return $response->then(function (ResponseInterface $response) use ($that) {
            return $that->create($that->parse((string)$response->getBody()));
        });
    }

    public function request($url)
    {
        return $this->http->get($url);
    }

    public function parse($data)
    {
        return json_decode($data, true);
    }

    public function create(array $data)
    {
        return $this->resultFactory->create($data);
    }
}
