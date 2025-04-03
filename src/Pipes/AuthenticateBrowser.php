<?php

namespace TrueRcm\LaravelWebscrape\Pipes;

use Illuminate\Support\Facades\Log;
use TrueRcm\LaravelWebscrape\Contracts\BrowserClient;
use TrueRcm\LaravelWebscrape\CrawlTraveller;
use TrueRcm\LaravelWebscrape\Events\CrawlAuthFailed;
use TrueRcm\LaravelWebscrape\Exceptions\CrawlException;

class AuthenticateBrowser
{
    public function __construct(
        protected BrowserClient $browser
    ) {
    }

    /**
     * @param \TrueRcm\LaravelWebscrape\CrawlTraveller $traveller
     * @param \Closure $next
     * @return mixed
     */
    public function handle(CrawlTraveller $traveller, \Closure $next)
    {
        Log::info('Webscrape: enter-authentication');

        if($traveller->doNotCrawlOldPages()){
            $this->browser
                ->request('GET', $traveller->authUrl());

            $crawler = $this->browser
                ->submitForm($traveller->authButtonIdentifier(), $traveller->getCrawlingCredentials());

            if($crawler->getUri() == 'https://proview.caqh.org/Login?Type=PR'){
                CrawlAuthFailed::dispatch($traveller->subject()->id);
                throw CrawlException::authenticationFailed($traveller);
            }

            $traveller->subject()->update(['authenticated_at' => now()]);

            $traveller->setBrowser($this->browser);
        }

        Log::info('Webscrape: finished-authentication');

        return $next($traveller);
    }
}
