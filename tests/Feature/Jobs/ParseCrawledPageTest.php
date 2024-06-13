<?php

use Illuminate\Support\Facades\Bus;
use TrueRcm\LaravelWebscrape\Exceptions\CrawlException;
use TrueRcm\LaravelWebscrape\Jobs\ParseCrawledPage;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use TrueRcm\LaravelWebscrape\Tests\Fixtures\ParseHtmlJob;

it('will throw exception when parsing job not found', function () {
    $this->expectException(CrawlException::class);

    $crawlResult = CrawlResult::factory()->create(['handler' => '']);

    $job = new ParseCrawledPage($crawlResult);

    $job->handle();
});

it('will handle dispatching the job to parse crawled page', function () {
    Bus::fake();

    $crawlResult = CrawlResult::factory()->create(['handler' => ParseHtmlJob::class]);

    $job = new ParseCrawledPage($crawlResult);

    $job->handle();

    Bus::assertDispatched(ParseHtmlJob::class);
});
