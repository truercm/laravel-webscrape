<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use TrueRcm\LaravelWebscrape\Contracts\CrawlResult;
use TrueRcm\LaravelWebscrape\CrawlTraveller;

class ParsePagesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected Collection $pages
    ) {
    }

    /**
     * Handle crawling the subject.
     *
     * @return void
     */
    public function handle(): void
    {
        Log::info('Webscrape: enter-parser-job');

        $this->pages->each(fn (CrawlResult $page) => ParseCrawledPage::dispatch($page));
    }
}

