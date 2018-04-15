<?php

use Clue\React\Packagist\Api\Client;
use React\Promise\Deferred;
use RingCentral\Psr7\Response;
use React\Promise;

class ClientTest extends TestCase
{
    private $browser;
    private $client;

    public function setUp()
    {
        $this->browser = $this->getMockBuilder('Clue\React\Buzz\Browser')->disableOriginalConstructor()->getMock();
        $this->browser->expects($this->any())->method('withBase')->willReturn($this->browser);

        $this->client = new Client($this->browser);
    }

    public function testGet()
    {
        $this->setupBrowser('/packages/clue%2Fzenity-react.json', $this->createResponsePromise('{"package":{"name":"clue\/zenity-react", "versions": {}}}'));

        $this->expectPromiseResolve($this->client->get('clue/zenity-react'));
    }

    public function testAll()
    {
        $this->setupBrowser('/packages/list.json', $this->createResponsePromise('{"packageNames":["a/a", "b/b"]}'));

        $this->expectPromiseResolve($this->client->all());
    }

    public function testAllVendor()
    {
        $this->setupBrowser('/packages/list.json?vendor=a', $this->createResponsePromise('{"packageNames":["a/a"]}'));

        $this->expectPromiseResolve($this->client->all(array('vendor' => 'a')));
    }

    public function testSearch()
    {
        $this->setupBrowser('/search.json?q=zenity', $this->createResponsePromise('{"results":[{"name":"clue\/zenity-react","description":"Build graphical desktop (GUI) applications in PHP","url":"https:\/\/packagist.org\/packages\/clue\/zenity-react","downloads":57,"favers":0,"repository":"https:\/\/github.com\/clue\/reactphp-zenity"}],"total":1}'));

        $this->expectPromiseResolve($this->client->search('zenity'));
    }

    public function testSearchSpecialWithNoResults()
    {
        $this->setupBrowser('/search.json?q=%3C%C3%A4%3E', $this->createResponsePromise('{"results":[],"total":0}'));

        $this->expectPromiseResolve($this->client->search('<ä>'));
    }

    public function testSearchPagination()
    {
        $this->browser->expects($this->exactly(2))
            ->method('get')
            ->will($this->onConsecutiveCalls(
                $this->createResponsePromise('{"results":[{"name":"clue\/zenity-react","description":"Build graphical desktop (GUI) applications in PHP","url":"https:\/\/packagist.org\/packages\/clue\/zenity-react","downloads":57,"favers":0,"repository":"https:\/\/github.com\/clue\/reactphp-zenity"}],"total":2, "next": ""}'),
                $this->createResponsePromise('{"results":[{"name":"clue\/zenity-react","description":"Build graphical desktop (GUI) applications in PHP","url":"https:\/\/packagist.org\/packages\/clue\/zenity-react","downloads":57,"favers":0,"repository":"https:\/\/github.com\/clue\/reactphp-zenity"}],"total":2}')
            ));

        $this->expectPromiseResolve($this->client->search('zenity'));
    }

    public function testHttpError()
    {
        $this->setupBrowser('/packages/clue%2Finvalid.json', $this->createRejectedPromise(new RuntimeException('error')));

        $this->expectPromiseReject($this->client->get('clue/invalid'));
    }

    private function setupBrowser($expectedUrl, $promise)
    {
        $this->browser->expects($this->once())
             ->method('get')
             ->with($this->equalTo($expectedUrl), array())
             ->will($this->returnValue($promise));
    }

    private function createResponsePromise($fakeResponseBody)
    {
        $response = $this->getMockBuilder('Psr\Http\Message\ResponseInterface')->getMock();
        $response->expects($this->once())->method('getBody')->willReturn($fakeResponseBody);

        return Promise\resolve($response);
    }

    private function createRejectedPromise($reason)
    {
        $d = new Deferred();
        $d->reject($reason);
        return $d->promise();
    }
}
