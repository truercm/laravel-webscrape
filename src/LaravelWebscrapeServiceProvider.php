<?php

namespace TrueRcm\LaravelWebscrape;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Symfony\Component\Panther\Client;
use TrueRcm\LaravelWebscrape\Contracts\BrowserClient;
use TrueRcm\LaravelWebscrape\Contracts\CrawlResult;
use TrueRcm\LaravelWebscrape\Contracts\CrawlSubject;
use TrueRcm\LaravelWebscrape\Contracts\CrawlTarget;
use TrueRcm\LaravelWebscrape\Contracts\CrawlTargetUrl;
use TrueRcm\LaravelWebscrape\Services\TextExtractorService;

class LaravelWebscrapeServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-webscrape')
            ->hasConfigFile()
            ->hasMigrations([
                'create_crawl_results_table',
                'create_crawl_subjects_table',
                'create_crawl_target_urls_table',
                'create_crawl_targets_table',
            ]);
    }

    public function packageBooted(): void
    {
        $this->registerModelBindings();
    }

    public function packageRegistered(): void
    {
        $this->registerInstances();
    }

    /**
     * Register contract bindings to models.
     */
    protected function registerModelBindings(): void
    {
        $this->app->bind(CrawlResult::class, fn ($app) => $app->make($app->config['webscrape.models.result']));
        $this->app->bind(CrawlSubject::class, fn ($app) => $app->make($app->config['webscrape.models.subject']));
        $this->app->bind(CrawlTarget::class, fn ($app) => $app->make($app->config['webscrape.models.target']));
        $this->app->bind(CrawlTargetUrl::class, fn ($app) => $app->make($app->config['webscrape.models.target_url']));
    }

    /**
     * Register singleton instances.
     */
    protected function registerInstances(): void
    {
        $this->app->singleton('text-extractor',
            fn ($app) => $app->make(TextExtractorService::class)
        );

        $this->app->singleton(BrowserClient::class, function ($app) {
            /* locate the selenium instance */
            $driver = $app['config']->get('webscrape.selenium_driver_url');
            /* create a new client */
            $client = Client::createSeleniumClient($driver);

            return new Browser($client);
        });
    }
}
