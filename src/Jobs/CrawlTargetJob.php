<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

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
        Log::info("Webscrape: initiated");

        CrawlStarted::dispatch($this->traveller->subject());

        $pipeline
            ->send($this->traveller)
            ->through([
                AuthenticateBrowser::class,
                CrawlPages::class,
                CloseBrowser::class,
            ])->then(function (CrawlTraveller $traveller) {

                //Extracts the IDs to fix serialization issue
                $pages = $traveller->getCrawledPages()->pluck('id');
                $subjectKey = $traveller->subject()->getKey();

                Log::info("Webscrape: {$pages->count()} Pages crawled");

                /* define the bus batch */
                $batch = Bus::batch([])
                    ->then(function($batch) use($subjectKey, $pages){
                        $batch2 = Bus::batch([]);
                        /* add jobs to the batch */
                        $pages
                        ->map(fn($id) => new PersistParseResult($id)) // Persist Parse result with Result IDs
                        ->pipe(fn(Collection $all) => $batch2->add($all));

                        $batch2->add([new ProcessParsedResultsJob($subjectKey, $pages)])
                            ->dispatch();
                    })
                    ->finally(fn($batch) => CrawlCompleted::dispatch($subjectKey));

                /* add jobs to the batch */
                $pages
                ->map(fn($id) => new ParseCrawledPage($id)) // Creates ParseCrawledPage jobs with IDs
                ->pipe(fn(Collection $all) => $batch->add($all)); // Adds jobs to the batch

                $batch->allowFailures()->dispatch();

                Log::info('Webscrape: bus dispatched');
            });
    }

    public function failed(\Throwable $exception): void
    {
        $subject = $this->traveller->subject();

        UpdateCrawlSubject::run($subject, [
            'result' => []
        ]);

        Log::error("CrawlTargetJob Error: Job failed for subject {$subject->id}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        CrawlFailed::dispatch($subject);
    }
}
