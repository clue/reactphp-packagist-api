<?php

require __DIR__ . '/../vendor/autoload.php';

use Clue\Packagist\React\Client;
use Packagist\Api\Result\Package;
use Clue\React\Buzz\Browser;

$loop = React\EventLoop\Factory::create();
$browser = new Browser($loop);
$client = new Client($browser);

$client->search('packagist')->then(function ($result) {
    var_dump('found ' . count($result) . ' packages matching "packagist"');
    //var_dump($result);
}, function ($error) {
    echo $e;
});

$client->get('clue/phar-composer')->then(function (Package $package) {
    var_dump($package->getName(), $package->getDescription());
});

$client->get('clue/graph')->then(function (Package $package) {
    var_dump($package->getName(), $package->getDescription());
});

$loop->run();
