<?php

namespace TrueRcm\LaravelWebscrape\Pipes;

use TrueRcm\LaravelWebscrape\Contracts\CrawlResult;
use TrueRcm\LaravelWebscrape\Jobs\ParseCrawledPage;
use TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller;

class ParsePages
{

    /**
     * @param \TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller $traveller
     * @param \Closure $next
     * @return mixed
     */
    public function handle(CrawlTraveller $traveller, \Closure $next)
    {
        $traveller->getCrawledPages()
            ->each(fn(CrawlResult $page) => ParseCrawledPage::dispatch($page));

        return $next($traveller);
    }
}
