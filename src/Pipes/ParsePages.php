<?php

namespace TrueRcm\LaravelWebscrape\Pipes;

use TrueRcm\LaravelWebscrape\Contracts\CrawlResult;
use TrueRcm\LaravelWebscrape\CrawlTraveller;
use TrueRcm\LaravelWebscrape\Jobs\ParseCrawledPage;

class ParsePages
{
    /**
     * @param \TrueRcm\LaravelWebscrape\CrawlTraveller $traveller
     * @param \Closure $next
     * @return mixed
     */
    public function handle(CrawlTraveller $traveller, \Closure $next)
    {
        $traveller->getCrawledPages()
            ->each(fn (CrawlResult $page) => ParseCrawledPage::dispatch($page));

        return $next($traveller);
    }
}
