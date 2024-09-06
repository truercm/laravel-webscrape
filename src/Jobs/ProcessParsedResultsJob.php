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
        Log::info('Webscrape: initiate-final-parser-job');

        $finalResult = ParseFinalResult::run($this->pages)
            ->collapse()
            ->toArray();

        UpdateCrawlSubject::run($this->subject, [
                'result' => $finalResult
            ]);

        Log::info('Webscrape: finished-final-parser-job');
    }
}

