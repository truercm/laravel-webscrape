<?php

namespace TrueRcm\LaravelWebscrape\Pipes;

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
        $this->browser
            ->request('GET', $traveller->authUrl());

        $crawler = $this->browser
            ->submitForm($traveller->authButtonIdentifier(), $traveller->getCrawlingCredentials());

        throw_if(
            $crawler->getUri() == $traveller->authUrl() /* is not good */,
            CrawlException::authenticationFailed($traveller)
        );

        $traveller->subject()->touch('authenticated_at');

        $traveller->setBrowser($this->browser);

        return $next($traveller);
    }
}
