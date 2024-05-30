<?php

namespace TrueRcm\LaravelWebscrape\Pipes;

use Symfony\Component\BrowserKit\HttpBrowser;
use TrueRcm\LaravelWebscrape\Exceptions\CrawlException;
use TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller;
use Symfony\Component\Panther\Client;

class AuthenticateBrowser
{
    protected ?Client $browser=null;

    public function __construct() {
        $this->browser = Client::createSeleniumClient($_ENV['SELENIUM_DRIVER_URL']);
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

    /**
     * @return \Symfony\Component\Panther\Client
     */
    public function getBrowser(): Client
    {
        return $this->browser ?: Client::createSeleniumClient($_ENV['SELENIUM_DRIVER_URL']);
    }
}
