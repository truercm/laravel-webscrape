<?php

namespace TrueRcm\LaravelWebscrape\Pipes;

use TrueRcm\LaravelWebscrape\Actions\ParseFinalResult;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlSubject;
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
        $finalResult = resolve(ParseFinalResult::class)->run($traveller->getCrawledPages());

        resolve(UpdateCrawlSubject::class)->run($traveller->subject(), ['result' => $finalResult->collapse()->toArray()]);

        return $next($traveller);
    }
}
