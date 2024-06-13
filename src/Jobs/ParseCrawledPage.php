<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TrueRcm\LaravelWebscrape\Exceptions\CrawlException;
use TrueRcm\LaravelWebscrape\Contracts\CrawlResult;

class ParseCrawledPage implements ShouldQueue
{
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
     */
    public function handle(): void
    {
        $this->handler()
            ->dispatch($this->crawlResult);
    }

    /**
     * @return \Illuminate\Foundation\Bus\Dispatchable
     * @throws \Throwable
     */
    protected function handler()
    {
        throw_unless(
            class_exists($this->crawlResult->handler),
            CrawlException::parsingJobNotFound($this->crawlResult)
        );

        return resolve($this->crawlResult->handler);
    }
}
