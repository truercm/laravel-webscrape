![](https://banners.beyondco.de/Webscrape.png?theme=light&packageManager=composer+require&packageName=truercm%2Flaravel-webscrape&pattern=diagonalLines&style=style_1&description=Scrape+web+pages+within+Laravel+application&md=1&showWatermark=0&fontSize=100px&images=cloud-download)

# Webscrape

    Scrape web pages with a Laravel application.

## Installation

You can install the package via composer:

```bash
composer require truercm/laravel-webscrape
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-webscrape-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-webscrape-config"
```

This is the contents of the published config file:

```php
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

    /*
     |--------------------------------------------------------------------------
     | Selenium driver url
     |--------------------------------------------------------------------------
     */
    'selenium_driver_url' => env('SELENIUM_DRIVER_URL', null),
];
```

Laravel Webcrawler uses Selenium to crawl the pages, so make sure you have [it installed](https://github.com/SeleniumHQ/docker-selenium).

## Usage

This is a generic package, you would need to implement all the crawling steps yourself.

The high concept overview involves:
1. Having a CrawlTarget - the model, containing the entry point to the list of pages you need to crawl
2. Crawl subject - a model that connects the credentials with the crawl target

Once you have registered a target, you can:

1. Initialize subject with credentials and target urls
2. Start remote url crawling and processing the result

```php

    $crawlSubject = \TrueRcm\LaravelWebscrape\Actions\StoreCrawlSubject::run([
        'model_type' => App\Models\User::class,
        'model_id' => 1,
        'crawl_target_id' => 1,
        'credentials' => ['values' => 'that would be piped', 'into' => 'crawl target'],
]);
```

and from here:
```php
resolve($crawlSubject->crawlTarget->crawling_job)
    ->dispatch($crawlSubject);
```

3. After the job is finished we have final result in CrawlSubject's result column

```php
    $crawlSubject->result;
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Purnendu Chandan](https://github.com/Purnendu-extreme)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
