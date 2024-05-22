<?php

use Illuminate\Support\Facades\Bus;
use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\Jobs\ParseCrawledPage;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use TrueRcm\LaravelWebscrape\Pipes\ParsePages;
use TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller;

it('will parse crawled pages html', function () {
    Bus::fake();

    $crawledResults = CrawlResult::factory()
        ->count(2)
        ->create();

    $stub = $this->mock(CrawlTraveller::class, function (MockInterface $mock) use ($crawledResults) {
        $mock->expects('getCrawledPages')
            ->andReturn($crawledResults);
    });

    app(ParsePages::class)
        ->handle($stub, function (CrawlTraveller $traveller) use ($stub) {
            $this->assertSame($stub, $traveller);
        });

    Bus::assertDispatched(ParseCrawledPage::class, 2);
});
