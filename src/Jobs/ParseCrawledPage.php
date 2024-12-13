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
//use TrueRcm\LaravelWebscrape\Models\CrawlResult;

class ParseCrawledPage implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected int $crawlResultId; // Holds the ID of the CrawlResult

    public function __construct(int $crawlResultId)
    {
        $this->crawlResultId = $crawlResultId; // Store the ID
    }

    /**
     * Handle parsing of the crawled page.
     * @throws \Throwable
     */
    public function handle(): void
    {
        // Retrieve the CrawlResult object from the database or any other data source
//        $crawlResult = $this->getCrawlResult();

//        if (!$crawlResult) {
//            Log::error("Webscrape: CrawlResult not found for ID {$this->crawlResultId}");
//            return;
//        }

        Log::info("Webscrape: enter-parsing-result-job {$this->crawlResultId}");

        $this->handler()
            ->dispatch($this->crawlResultId);

        Log::info("Webscrape: dispatched-parsing-result-job {$this->getCrawlResult()->handler}");
    }

    /**
     * Retrieve the CrawlResult by ID.
     *
     * @return \TrueRcm\LaravelWebscrape\Contracts\CrawlResult|null
     */
//    protected function getCrawlResult(): ?CrawlResult
//    {
//        // Assuming CrawlResult is an Eloquent model or a repository method
//        return app(CrawlResult::class)->find($this->crawlResultId);
//    }

    /**
     * @return \TrueRcm\LaravelWebscrape\Contracts\ParsePage
     * @throws \Throwable
     */
    protected function handler(): ParsePage
    {
        throw_unless(
            class_exists($this->getCrawlResult()->handler),
            CrawlException::parsingJobNotFound($this->getCrawlResult())
        );

        return resolve($this->getCrawlResult()->handler);
    }

    /**
     * Retrieve the CrawlResult by ID.
     *
     * @return \TrueRcm\LaravelWebscrape\Contracts\CrawlResult|null
     */
    protected function getCrawlResult(): ?CrawlResult
    {
        // Assuming CrawlResult is an Eloquent model or a repository method
        return app(CrawlResult::class)->find($this->crawlResultId);
    }
}
