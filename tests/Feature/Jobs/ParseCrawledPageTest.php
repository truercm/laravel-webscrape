<?php

use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use TrueRcm\LaravelWebscrape\Exceptions\CrawlException;
use TrueRcm\LaravelWebscrape\Jobs\ParseCrawledPage;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use TrueRcm\LaravelWebscrape\Tests\Fixtures\ParseHtmlJob;

it('will throw exception when crawl result not found', function () {
    Log::spy();
    $this->expectException(CrawlException::class);

    $job = new ParseCrawledPage(111);

    $job->handle();

    Log::shouldHaveReceived('error')->withArgs(function ($msg, $context) {
        return str_contains($msg, 'CrawlResult not found for ID 111');
    });
});

it('will throw exception when parsing job not found', function () {
    $this->expectException(CrawlException::class);

    $crawlResult = CrawlResult::factory()->create(['id' => 111, 'handler' => '']);

    $job = new ParseCrawledPage(111);

    $job->handle();
});

it('will handle dispatching the job to parse crawled page', function () {
    Bus::fake();

    $crawlResult = CrawlResult::factory()->create(['id' => 111, 'handler' => ParseHtmlJob::class]);

    $job = new ParseCrawledPage(111);

    $job->handle();

    Bus::assertBatched(function ( $batch) {
        return $batch->jobs->contains(fn($job) => $job instanceof ParseHtmlJob);
    });
});
