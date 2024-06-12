<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TrueRcm\LaravelWebscrape\CrawlTraveller;
use TrueRcm\LaravelWebscrape\Events\CrawlCompleted;
use TrueRcm\LaravelWebscrape\Events\CrawlStarted;
use TrueRcm\LaravelWebscrape\Pipes\AuthenticateBrowser;
use TrueRcm\LaravelWebscrape\Pipes\CrawlPages;
use TrueRcm\LaravelWebscrape\Pipes\ParsePages;
use TrueRcm\LaravelWebscrape\Pipes\ProcessParsingResults;

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
        CrawlStarted::dispatch($this->traveller->subject());

        $pipeline
            ->send($this->traveller)
            ->through([
                AuthenticateBrowser::class,
                CrawlPages::class,
                ParsePages::class,
                ProcessParsingResults::class,
            ])->then(fn (CrawlTraveller $traveller) => CrawlCompleted::dispatch($traveller->subject()));
    }
}
