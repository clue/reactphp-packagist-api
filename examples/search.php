<?php

require __DIR__ . '/../vendor/autoload.php';

use Clue\React\Packagist\Api\Client;
use Packagist\Api\Result\Package;
use Clue\React\Buzz\Browser;

$loop = React\EventLoop\Factory::create();
$browser = new Browser($loop);
$client = new Client($browser);

$client->search('reactphp')->then(function ($result) {
    var_dump('found ' . count($result) . ' packages matching "reactphp"');
    //var_dump($result);
}, 'printf');

$client->get('clue/phar-composer')->then(function (Package $package) {
    var_dump($package->getName(), $package->getDescription());
}, 'printf');

$client->get('clue/graph')->then(function (Package $package) {
    var_dump($package->getName(), $package->getDescription());
}, 'printf');

$loop->run();
