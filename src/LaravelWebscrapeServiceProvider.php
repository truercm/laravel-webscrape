<?php

namespace TrueRcm\LaravelWebscrape;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use TrueRcm\LaravelWebscrape\Commands\LaravelWebscrapeCommand;

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
            ->hasViews()
            ->hasMigration('create_laravel-webscrape_table')
            ->hasCommand(LaravelWebscrapeCommand::class);
    }
}
