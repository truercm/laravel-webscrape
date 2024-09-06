<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use TrueRcm\LaravelWebscrape\Contracts\CrawlResult;
use TrueRcm\LaravelWebscrape\Contracts\ParsePage;
use TrueRcm\LaravelWebscrape\Exceptions\CrawlException;

class ParseCrawledPage implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected CrawlResult $crawlResult
    ) {
    }

    /**
     * Handle parsing of the crawled page.
     * @throws \Throwable
     */
    public function handle(): void
    {
        Log::info("Webscrape: enter-parsing-result-job {$this->crawlResult->id}");

        $this->handler()
            ->dispatch($this->crawlResult);

        Log::info("Webscrape: dispatched-parsing-result-job {$this->crawlResult->handler}");
    }

    /**
     * @return \TrueRcm\LaravelWebscrape\Contracts\ParsePage
     * @throws \Throwable
     */
    protected function handler(): ParsePage
    {
        throw_unless(
            class_exists($this->crawlResult->handler),
            CrawlException::parsingJobNotFound($this->crawlResult)
        );

        return resolve($this->crawlResult->handler);
    }
}
