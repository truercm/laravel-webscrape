<?php

namespace TrueRcm\LaravelWebscrape\Pipes;

use Symfony\Component\DomCrawler\Crawler;
use TrueRcm\LaravelWebscrape\Actions\AddCrawlResult;
use TrueRcm\LaravelWebscrape\CrawlTraveller;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;

class CrawlPages
{
    /**
     * @param \TrueRcm\LaravelWebscrape\CrawlTraveller $traveller
     * @param \Closure $next
     * @return mixed
     */
    public function handle(CrawlTraveller $traveller, \Closure $next)
    {
        foreach ($traveller->targets() as $target) {
            /* it cannot be try/catch as we want to continue on fail to @todo extract into a sync job, possibly, if it does not serialize the browser */
            $traveller->getBrowser()->request('GET', $target->url);
            $crawler = $traveller->getBrowser()->waitForInvisibility('div#loading');
            $crawler = new Crawler($crawler->html());
            $responseCode = $traveller->getBrowser()->executeScript('return window.performance.getEntries()[0].responseStatus');

            $page = AddCrawlResult::run($traveller->subject(), $target, [
                'url' => $target->url,
                'status' => $responseCode,
                'body' => $crawler->filter('body')->html(), /* html */
                'handler' => $target->handler,
                'process_status' => CrawlResultStatus::PENDING->value,
            ]);

            $traveller->addCrawledPage($page);
        }

        return $next($traveller);
    }
}
