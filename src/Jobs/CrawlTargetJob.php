<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlSubject;
use TrueRcm\LaravelWebscrape\CrawlTraveller;
use TrueRcm\LaravelWebscrape\Events\CrawlCompleted;
use TrueRcm\LaravelWebscrape\Events\CrawlFailed;
use TrueRcm\LaravelWebscrape\Events\CrawlStarted;
use TrueRcm\LaravelWebscrape\Pipes\AuthenticateBrowser;
use TrueRcm\LaravelWebscrape\Pipes\CloseBrowser;
use TrueRcm\LaravelWebscrape\Pipes\CrawlPages;

class CrawlTargetJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected CrawlTraveller $traveller
    ) {
    }

    /**
     * Handle crawling the subject.
     *
     * @param \Illuminate\Pipeline\Pipeline $pipeline
     * @return void
     */
    public function handle(Pipeline $pipeline): void
    {
        Log::info("Webscrape: initiated for subject ID: {$this->traveller->subject()->getKey()}");

        CrawlStarted::dispatch($this->traveller->subject());

        try {
            $pipeline
                ->send($this->traveller)
                ->through([
                    AuthenticateBrowser::class,
                    CrawlPages::class,
                    CloseBrowser::class,
                ])
                ->then(function (CrawlTraveller $traveller) {
                    $this->dispatchPostCrawlJobs($traveller);
                });
        } catch (\Throwable $exception) {
            $this->handleCrawlFailure($exception);
        } finally {
            $this->traveller->clearBrowser();
        }
    }

    /**
     * Dispatch the jobs that should run after crawling is complete.
     */
    protected function dispatchPostCrawlJobs(CrawlTraveller $traveller): void
    {
        $pages = $traveller->getCrawledPages()->pluck('id');
        $subjectKey = $traveller->subject()->getKey();

        Log::info("Webscrape: {$pages->count()} Pages crawled for subject ID: {$subjectKey}");

        Bus::batch([
            // Create jobs for parsing
            $pages->map(fn($id) => new ParseCrawledPage($id)),
        ])
        ->then(function(Batch $batch) use($subjectKey, $pages) { // Use type hint Batch
            // Create jobs for persisting parse results and processing them
            Bus::batch([
                $pages->map(fn($id) => new PersistParseResult($id)),
                [new ProcessParsedResultsJob($subjectKey, $pages)],
            ])->dispatch();
        })
        ->finally(fn(Batch $batch) => CrawlCompleted::dispatch($subjectKey)) // Use type hint Batch
        ->allowFailures()
        ->dispatch();

        Log::info('Webscrape: batch dispatched');
    }

    public function failed(\Throwable $exception): void
    {
        $this->handleCrawlFailure($exception);
    }

    /**
     * Centralized crawl failure handling.
     */
    protected function handleCrawlFailure(\Throwable $exception): void
    {
        $subject = $this->traveller->subject();

        UpdateCrawlSubject::run($subject, [
            'result' => []
        ]);

        Log::error("CrawlTargetJob Error: Job failed for subject {$subject->getKey()}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        CrawlFailed::dispatch($subject);
    }
}
