<?php

namespace TrueRcm\LaravelWebscrape\Facades;

use Illuminate\Support\Facades\Facade;
use TrueRcm\LaravelWebscrape\Contracts\BrowserClient;

/**
 * @see \TrueRcm\LaravelWebscrape\LaravelWebscrape
 */
class WebscrapeBrowser extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BrowserClient::class;
    }
}
