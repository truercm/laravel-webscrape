<?php

namespace TrueRcm\LaravelWebscrape\Exceptions;

use TrueRcm\LaravelWebscrape\Contracts\CrawlResult;
use TrueRcm\LaravelWebscrape\CrawlTraveller;

final class CrawlException extends \Exception
{
    /**
     * @param \TrueRcm\LaravelWebscrape\CrawlTraveller $traveller
     * @return static
     */
    public static function noBrowserSetUp(CrawlTraveller $traveller): static
    {
        $message = __('Crawling traveller does not have a browser set up');

        return new static($message);
    }

    /**
     * @param \TrueRcm\LaravelWebscrape\CrawlTraveller $traveller
     * @return static
     */
    public static function browsingFailed(CrawlTraveller $traveller): static
    {
        $message = __('Browsing failed for url :url', [
            'url' => $traveller->authUrl(),
        ]);

        return new static($message);
    }

    /**
     * @param \TrueRcm\LaravelWebscrape\CrawlTraveller $traveller
     * @return static
     */
    public static function authenticationFailed(CrawlTraveller $traveller): static
    {
        $message = __('Authentication failed for given credentials');

        return new static($message);
    }

    /**
     * @param \TrueRcm\LaravelWebscrape\Contracts\CrawlResult $crawlResult
     * @return static
     */
    public static function parsingFailed(CrawlResult $crawlResult): static
    {
        $message = __('Parsing failed for the page with url :url', [
            'url' => $crawlResult->url,
        ]);

        return new static($message);
    }

    /**
     * @param \TrueRcm\LaravelWebscrape\Contracts\CrawlResult $crawlResult
     * @return static
     */
    public static function parsingJobNotFound(CrawlResult $crawlResult): static
    {
        $message = __('Parsing job not found for the page with url :url', [
            'url' => $crawlResult->url,
        ]);

        return new static($message);
    }
}
