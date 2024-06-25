<?php

use Symfony\Component\Panther\Client;
use TrueRcm\LaravelWebscrape\Browser;

it('forwards method calls to the Symfony Panther Client', function () {
    $client = \Mockery::mock(Client::class);
    $browser = new Browser($client);

    // Assume `visit` is a method provided by Symfony Panther Client
    $client->shouldReceive('visit')
        ->once()
        ->with('http://example.com')
        ->andReturn('Visited http://example.com');

    expect($browser->visit('http://example.com'))->toBe('Visited http://example.com');
});
