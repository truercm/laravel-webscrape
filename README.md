![](https://banners.beyondco.de/Webscrape.png?theme=light&packageManager=composer+require&packageName=truercm%2Flaravel-webscrape&pattern=diagonalLines&style=style_1&description=Scrape+web+paged+within+Laravel+application&md=1&showWatermark=0&fontSize=100px&images=cloud-download)

# Webscrape 

Scrape web paged within Laravel application

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

Register package within `repositories` key of you `composer.json`:

```json
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/truercm/laravel-webscrape"
        }
    ],
```

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
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="laravel-webscrape-views"
```

## Usage

1. Initialize subject with credentials and target urls
2. Start remote url crawling and processing the result

```php

    $crawlSubject = CrawlSubject::find(1);

    resolve($crawlSubject->crawlTarget->crawling_job)->dispatch($crawlSubject);

```

3. after job is finished we have final result in CrawlSubject's result column

```php

    $crawlSubject->fresh()->result;
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
