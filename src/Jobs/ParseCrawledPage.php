<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
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
        Log::info("Webscrape: enter-parsing-result-job {$this->crawlResultId}");

        $crawlResult = $this->getCrawlResult();

        if (!$crawlResult) {
            Log::error("CrawlResult not found for ID {$this->crawlResultId} in handler()");
            throw CrawlException::crawlResultNotFound($this->crawlResultId);
        }

        $batch = $this->batch() ?: Bus::batch([]);

        $batch->add([$this->handler($crawlResult)]);

        if($batch->jobs->count() == 1 AND  $batch->jobs->first() instanceof $crawlResult->handler){
            $batch
                ->dispatch();
        }

        Log::info("Webscrape: dispatched-parsing-result-job {$crawlResult->handler}");
    }

    /**
     * @param \TrueRcm\LaravelWebscrape\Contracts\CrawlResult $crawlResult
     * @return \TrueRcm\LaravelWebscrape\Contracts\ParsePage
     * @throws \Throwable
     */
    protected function handler(CrawlResult $crawlResult): ParsePage
    {
        throw_unless(
            class_exists($crawlResult->handler),
            CrawlException::parsingJobNotFound($crawlResult)
        );

        return resolve($crawlResult->handler, ['crawlResultId' => $this->crawlResultId]);
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
