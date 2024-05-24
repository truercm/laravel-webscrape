<?php

use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use TrueRcm\LaravelWebscrape\Exceptions\CrawlException;
use TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller;
use Symfony\Component\BrowserKit\Response;
use Mockery\MockInterface;

it('will handle browsingFailed exception', function () {

    $traveler = $this->mock(CrawlTraveller::class, function (MockInterface $mock) {
        $mock->expects('authUrl')
            ->andReturn('http:://wrong.site');
    });

    $stub = CrawlException::browsingFailed($traveler);

    $this->assertInstanceOf(CrawlException::class, $stub);

    $this->assertEquals(
        'Browsing failed for url http:://wrong.site',
        $stub->getMessage()
    );
});

it('will handle authenticationFailed exception', function () {

    $traveler = resolve(CrawlTraveller::class);

    $stub = CrawlException::authenticationFailed($traveler, new Response());

    $this->assertInstanceOf(CrawlException::class, $stub);

    $this->assertEquals(
        'Authentication failed for given credentials',
        $stub->getMessage()
    );
});

it('will handle parse failed exception', function () {

    $crawlResult = CrawlResult::factory()->create(['url' => 'http:://some.site']);

    $stub = CrawlException::parsingFailed($crawlResult);

    $this->assertInstanceOf(CrawlException::class, $stub);

    $this->assertEquals(
        'Parsing failed for the page with url http:://some.site',
        $stub->getMessage()
    );
});
