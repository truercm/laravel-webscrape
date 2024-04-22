<?php

namespace TrueRcm\LaravelWebscrape\Pipes;

use TrueRcm\LaravelWebscrape\Actions\AddCrawlResult;
use TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller;

class CrawlPages
{

    /**
     * @param \TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller $traveller
     * @param \Closure $next
     * @return mixed
     */
    public function handle(CrawlTraveller $traveller, \Closure $next)
    {
        foreach($traveller->targets() as $target) {
            /* it cannot be try/catch as we want to continue on fail to @todo extract into a sync job, possibly, if it does not serialize the browser */
            $crawler = $traveller->getBrowser()->request('GET', $target->url);

            $response = $traveller->getBrowser()->getResponse();

            $page = AddCrawlResult::run($traveller->subject(), $target, [
                'url' => $target->url,
                'status' => $response->getStatusCode(),
                'body' => $crawler->filter('body')->html(), /* html*/
                'handler' => $target->handler,
                'process_status' => 'Pending',
            ]);

            $traveller->addCrawledPage($page);
        }

        return $next($traveller);
    }
}
