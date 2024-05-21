<?php

use TrueRcm\LaravelWebscrape\Jobs\ParseCrawledPage;
use TrueRcm\LaravelWebscrape\Jobs\ParsePersonalInfoPage;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use Illuminate\Support\Facades\Bus;

it('will handle dispatching the job to parse crawled page', function () {
    Bus::fake();

    $crawlResult = CrawlResult::factory()->create(['handler' => ParsePersonalInfoPage::class]);

    $job = new ParseCrawledPage($crawlResult);

    $job->handle();

    Bus::assertDispatched(ParsePersonalInfoPage::class);
});
