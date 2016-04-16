<?php

namespace Clue\React\Packagist\Api;

use Packagist\Api\Result\Factory;
use Clue\React\Buzz\Browser;
use Psr\Http\Message\ResponseInterface;
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

        $fetch = function ($url) use (&$results, $that, &$fetch) {
            return $that->request($url)->then(function (ResponseInterface $response) use (&$results, $that, $fetch) {
                $parsed = $that->parse((string)$response->getBody());
                $results = array_merge($results, $that->create($parsed));

                if (isset($parsed['next'])) {
                    return $fetch($parsed['next']);
                } else {
                    return $results;
                }
            });
        };

        return $fetch($url);
    }

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
