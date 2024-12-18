<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Contracts\CrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;

class PersistParseResult implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected int $crawlResultId; // Holds the ID of the CrawlResult
    protected CrawlResult $crawlResult;

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
        $this->crawlResult = $this->getCrawlResult();

        Log::info("Webscrape: enter-persist-parse-result-job for crawl-result {$this->crawlResult->id}");

        UpdateCrawlResult::run($this->crawlResult, $this->toArray());

        Cache::forget($this->cacheKey());

        Log::info("Webscrape: finished-persist-parse-result-job for crawl-result {$this->crawlResult->id}");
    }

    protected function toArray(): array
    {
        return [
            'processed_at' => now(),
            'result' => Cache::get($this->cacheKey()),
            'process_status' => CrawlResultStatus::COMPLETED,
        ];
    }

    /**
     * Retrieve the CrawlResult by ID.
     *
     * @return \TrueRcm\LaravelWebscrape\Contracts\CrawlResult|null
     */
    protected function getCrawlResult(): ?CrawlResult
    {
        // Assuming CrawlResult is an Eloquent model or a repository method
        $crawlResult = app(CrawlResult::class)->find($this->crawlResultId);

        if (!$crawlResult) {
            Log::error("CrawlResult not found for ID {$this->crawlResultId}");
        }

        return $crawlResult;
    }

    public function cacheKey(): string
    {
        return 'App.CrawlResult.'.$this->crawlResult->getKey().'.parsed';
    }
}
