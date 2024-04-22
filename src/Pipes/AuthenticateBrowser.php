<?php

namespace TrueRcm\LaravelWebscrape\Pipes;

use Symfony\Component\BrowserKit\HttpBrowser;
use Throwable;
use TrueRcm\LaravelWebscrape\Exceptions\CrawlException;
use TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller;

class AuthenticateBrowser
{
    public function __construct(
        protected HttpBrowser $browser
    ) {
    }


    /**
     * @param \TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller $traveller
     * @param \Closure $next
     * @return mixed
     */
    public function handle(CrawlTraveller $traveller, \Closure $next)
    {

        $this->browser
                ->request('GET', $traveller->authUrl());

        throw_if(
            $this->browser->getResponse()->getStatusCode() != 200 /* is not good*/,
            CrawlException::browsingFailed($traveller, $this->browser->getResponse())
        );

        $crawler = $this->browser
            ->submitForm($traveller->authButtonIdentifier(), $traveller->getCrawlingCredentials());

        throw_if(
            $crawler->getUri() == $traveller->authUrl() /* is not good*/,
            CrawlException::authenticationFailed($traveller, $this->browser->getResponse())
        );

        $traveller->subject()->touch('authenticated_at');

        $traveller->setBrowser($this->browser);

        return $next($traveller);
    }
}
