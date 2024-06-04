<?php

namespace TrueRcm\LaravelWebscrape\Facades;

use Illuminate\Support\Facades\Facade;
use TrueRcm\LaravelWebscrape\LaravelWebscrape as LaravelWebscrapeContract;

/**
 * @see \TrueRcm\LaravelWebscrape\LaravelWebscrape
 */
class LaravelWebscrape extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LaravelWebscrapeContract::class;
    }
}
