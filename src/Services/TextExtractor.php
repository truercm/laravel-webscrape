<?php

namespace TrueRcm\LaravelWebscrape\Services;

use Illuminate\Support\Facades\Facade;

class TextExtractor extends Facade
{
    /**
     * @return string
     */
    public static function getFacadeAccessor()
    {
        return 'text-extractor';
    }
}
