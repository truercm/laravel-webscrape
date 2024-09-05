<?php

namespace TrueRcm\LaravelWebscrape\Pipes;

use Illuminate\Support\Facades\Log;
use TrueRcm\LaravelWebscrape\Contracts\BrowserClient;
use TrueRcm\LaravelWebscrape\CrawlTraveller;
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

        $this->browser
            ->request('GET', $traveller->authUrl());

        $crawler = $this->browser
            ->submitForm($traveller->authButtonIdentifier(), $traveller->getCrawlingCredentials());

        throw_if(
            $crawler->getUri() == $traveller->authUrl() /* is not good */,
            CrawlException::authenticationFailed($traveller)
        );

        $traveller->subject()->touch();

        $traveller->setBrowser($this->browser);

        Log::info('Webscrape: finished-authentication');

        return $next($traveller);
    }
}
