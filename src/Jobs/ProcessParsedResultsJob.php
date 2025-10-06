<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use TrueRcm\LaravelWebscrape\Actions\ParseFinalResult;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlSubject;
use TrueRcm\LaravelWebscrape\Contracts\CrawlResult;
use TrueRcm\LaravelWebscrape\Contracts\CrawlSubject;

class ProcessParsedResultsJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected int $subjectId,
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

        $crawlSubject = app(CrawlSubject::class)->find($this->subjectId);
        $crawlResults = app(CrawlResult::class)->whereKey($this->pages)->get();

        $finalResult = ParseFinalResult::run($crawlResults)
            ->collapse()
            ->toArray();

        UpdateCrawlSubject::run($crawlSubject, [
                'result' => $finalResult
            ]);

        Log::info('Webscrape: finished-final-parser-job');
    }
}

