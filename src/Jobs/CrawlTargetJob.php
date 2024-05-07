<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Pipeline;
use TrueRcm\LaravelWebscrape\Events\CrawlCompleted;
use TrueRcm\LaravelWebscrape\Events\CrawlStarted;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;
use TrueRcm\LaravelWebscrape\Pipes\AuthenticateBrowser;
use TrueRcm\LaravelWebscrape\Pipes\CrawlPages;
use TrueRcm\LaravelWebscrape\Pipes\ParsePages;
use TrueRcm\LaravelWebscrape\Pipes\ProcessParsingResults;
use TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller;

class CrawlTargetJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected CrawlSubject $subject
    ) {
    }

    /*
     * "target" is remote site
     * "subject" is a single crawler run for a given target, parsed by a specific job
     * */

    public function handle()
    {
        $traveller = CrawlTraveller::make($this->subject);

        \TrueRcm\LaravelWebscrape\Models\CrawlResult::whereKey([2,3,4])->get()->each(fn($page) => $traveller->addCrawledPage($page));

        CrawlStarted::dispatch($this->subject);

        Pipeline::send($traveller)
            ->through([
                //AuthenticateBrowser::class,
                //CrawlPages::class,
                ParsePages::class,
                ProcessParsingResults::class,
            ])->then(fn(CrawlTraveller $traveller) => CrawlCompleted::dispatch($traveller->subject()));
    }
}
