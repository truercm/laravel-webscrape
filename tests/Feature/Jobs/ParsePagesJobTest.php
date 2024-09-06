<?php

namespace TrueRcm\LaravelWebscrape\Tests\Feature\Jobs;

use Illuminate\Support\Facades\Bus;
use TrueRcm\LaravelWebscrape\Jobs\ParseCrawledPage;
use TrueRcm\LaravelWebscrape\Jobs\ParsePagesJob;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use TrueRcm\LaravelWebscrape\Tests\TestCase;

class ParsePagesJobTest extends TestCase
{
    /** @test */
    public function it_will_handle_parse_pages_job(): void
    {
        Bus::fake();

        $crawledResults = CrawlResult::factory()
            ->count(2)
            ->create();

        $job = new ParsePagesJob($crawledResults);

        $job->handle();

        Bus::assertDispatched(ParseCrawledPage::class, 2);
    }
}
