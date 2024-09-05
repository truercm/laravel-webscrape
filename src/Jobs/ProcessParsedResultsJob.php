<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use TrueRcm\LaravelWebscrape\Actions\ParseFinalResult;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlSubject;
use TrueRcm\LaravelWebscrape\Contracts\CrawlSubject;
use TrueRcm\LaravelWebscrape\CrawlTraveller;

class ProcessParsedResultsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected CrawlSubject $subject,
        protected Collection $pages
    ) {
    }

    /**
     * Handle crawling the subject.
     *
     * @return void
     */
    public function handle(): void
    {
        Log::error('Webscrape: initiate-final-parser-job');

        $finalResult = resolve(ParseFinalResult::class)
            ->run($this->pages)
            ->collapse()
            ->toArray();

        resolve(UpdateCrawlSubject::class)
            ->run($this->subject, [
                'result' => $finalResult
            ]);

        Log::error('Webscrape: finished-final-parser-job');
    }
}

