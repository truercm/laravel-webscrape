<?php

namespace TrueRcm\LaravelWebscrape\Pipes;

use TrueRcm\LaravelWebscrape\CrawlTraveller;

class CloseBrowser
{
    /**
     * @param \TrueRcm\LaravelWebscrape\CrawlTraveller $traveller
     * @param \Closure $next
     * @return mixed
     */
    public function handle(CrawlTraveller $traveller, \Closure $next)
    {
        $traveller->clearBrowser();

        return $next($traveller);
    }
}
