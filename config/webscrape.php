<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Webscrape models
    |--------------------------------------------------------------------------
    */
    'models' => [

        /*
        |--------------------------------------------------------------------------
        | Subject model holds the credentials, target_id and the final scraping result
        |--------------------------------------------------------------------------
        */
        'subject' => TrueRcm\LaravelWebscrape\Models\CrawlSubject::class,

        /*
        |--------------------------------------------------------------------------
        | Target model stores the remote target, authentication url and processing job
        |--------------------------------------------------------------------------
        */
        'target' => TrueRcm\LaravelWebscrape\Models\CrawlTarget::class,

        /*
        |--------------------------------------------------------------------------
        | TargetUrl model collects all URLs for the Target
        |--------------------------------------------------------------------------
        */
        'target_url' => TrueRcm\LaravelWebscrape\Models\CrawlTargetUrl::class,

        /*
        |--------------------------------------------------------------------------
        | Url Result model stores processed results
        |--------------------------------------------------------------------------
        */
        'result' => TrueRcm\LaravelWebscrape\Models\CrawlResult::class,
    ],
];
