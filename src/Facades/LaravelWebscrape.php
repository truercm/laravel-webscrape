<?php

namespace TrueRcm\LaravelWebscrape\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \TrueRcm\LaravelWebscrape\LaravelWebscrape
 */
class LaravelWebscrape extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \TrueRcm\LaravelWebscrape\LaravelWebscrape::class;
    }
}
