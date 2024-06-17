<?php

use DG\BypassFinals;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\ProcessManager\BrowserManagerInterface;
use TrueRcm\LaravelWebscrape\Browser;

beforeEach(function () {
    BypassFinals::enable();
});

it('will forward calls to client from browser proxy', function () {
    $manager = $this->mock(BrowserManagerInterface::class, function ($mock) {
        $mock->shouldReceive('quit')->andReturnSelf();
    });

    $client = $this->partialMock(Client::class, fn () => new Client($manager));

    $client
        ->expects('whatever')
        ->with('foo')
        ->andReturnTrue();

    $stub = new Browser($client);

    $this->assertTrue($stub->whatever('foo'));
});
