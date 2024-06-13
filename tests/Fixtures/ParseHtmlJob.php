<?php

namespace TrueRcm\LaravelWebscrape\Tests\Fixtures;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TrueRcm\LaravelWebscrape\Contracts\ParsePage;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;

class ParseHtmlJob implements ShouldQueue, ParsePage
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected CrawlResult $crawlResult
    ) {
    }

    public function handle()
    {

    }
}
