<?php

namespace TrueRcm\LaravelWebscrape\Pipes;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use TrueRcm\LaravelWebscrape\Contracts\CrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller;

class ProcessParsingResults
{

    /**
     * @param \TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller $traveller
     * @param \Closure $next
     * @return mixed
     */
    public function handle(CrawlTraveller $traveller, \Closure $next)
    {
        $finalResult = collect();

        $traveller->getCrawledPages()
            ->filter(fn(CrawlResult $page) => (CrawlResultStatus::COMPLETED)->value == $page->fresh()->process_status)
            ->reject(fn(CrawlResult $page) => empty($page->crawlTargetUrl->result_fields))
            ->each(function( CrawlResult $page) use($finalResult){
                $resultFields = json_decode($page->crawlTargetUrl->result_fields, true);
                $result = json_decode($page->fresh()->result, true);
                $finalResult->push(Arr::only($result, $resultFields));
            });

        $traveller->subject()->update(['result' => $finalResult->collapse()]);

        return $next($traveller);
    }
}
