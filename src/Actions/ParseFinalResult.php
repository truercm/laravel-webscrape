<?php

namespace TrueRcm\LaravelWebscrape\Actions;

use Fls\Actions\Action;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use TrueRcm\LaravelWebscrape\Contracts\CrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;

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
            ->filter(fn (CrawlResult $page) => CrawlResultStatus::COMPLETED->value == $page->fresh()->process_status)
            ->reject(fn (CrawlResult $page) => empty($page->crawlTargetUrl->result_fields))
            ->each(function (CrawlResult $page) use ($finalResult) {
                $resultFields = json_decode($page->crawlTargetUrl->result_fields, true);
                $result = json_decode($page->fresh()->result, true);
                $finalResult->push(Arr::only($result, $resultFields));
            });

        return $finalResult;
    }
}
