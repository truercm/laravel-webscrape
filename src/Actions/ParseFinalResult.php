<?php

namespace TrueRcm\LaravelWebscrape\Actions;

use Fls\Actions\Action;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use TrueRcm\LaravelWebscrape\Contracts\CrawlResult;

class ParseFinalResult extends Action
{
    /**
     * @param \Illuminate\Support\Collection $crawledPages
     * @return \Illuminate\Support\Collection
     */
    public function handle(Collection $crawledPages): Collection
    {
        $finalResult = collect();

        $crawledPages
            ->filter(fn (CrawlResult $page) => $page->fresh()->process_status->isComplete())
            ->reject(fn (CrawlResult $page) => empty($page->crawlTargetUrl->result_fields))
            ->each(function (CrawlResult $page) use ($finalResult) {
                $resultFields = $page->crawlTargetUrl->result_fields;
                $result = $page->fresh()->result;
                $finalResult->push(Arr::only($result, $resultFields));
            });

        return $finalResult;
    }
}
