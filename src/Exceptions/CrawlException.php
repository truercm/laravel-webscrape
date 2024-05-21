<?php

namespace TrueRcm\LaravelWebscrape\Exceptions;

use Symfony\Component\BrowserKit\Response;
use TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller;

class CrawlException extends \Exception
{
    /**
     * @param \TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller $traveller
     * @param \Throwable $throwable
     * @return static
     */
    public static function browsingFailed(CrawlTraveller $traveller)
    {
        $message = __('Browsing failed for url :url', [
            'url' => $traveller->authUrl(),
        ]);

        return new static($message);
    }

    /**
     * @param \TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller $traveller
     * @param \Throwable $throwable
     * @return static
     */
    public static function authenticationFailed(CrawlTraveller $traveller, Response $response)
    {
        $message = __('Authentication failed for given credentials');

        return new static($message);
    }
}
