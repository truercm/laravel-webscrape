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
use TrueRcm\LaravelWebscrape\CrawlTraveller;
use TrueRcm\LaravelWebscrape\Events\CrawlCompleted;
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

                $pages = $traveller->getCrawledPages();
                $subject = $traveller->subject();

                Log::info("Webscrape: {$pages->count()} Pages crawled");

                /* define the bus batch */
                $batch = Bus::batch([])
                    ->then(fn($batch) => ProcessParsedResultsJob::dispatch($subject, $pages))
                    ->finally(fn($batch) => CrawlCompleted::dispatch($subject));

                /* prepare the batches */
                $pages->mapInto(ParseCrawledPage::class)
                    ->pipe(fn(Collection $all) => $batch->add($all));

                $batch->dispatch();

                Log::info('Webscrape: bus dispatched');
            });
    }
}
