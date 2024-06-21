<?php

use Symfony\Component\Panther\Client;
use TrueRcm\LaravelWebscrape\Browser;

it('will forward calls to client from browser proxy', function () {
    $client = $this->mock(Client::class);

    $client
        ->expects('whatever')
        ->with('foo')
        ->andReturnTrue();

    $stub = new Browser($client);

    $this->assertTrue($stub->whatever('foo'));
});
