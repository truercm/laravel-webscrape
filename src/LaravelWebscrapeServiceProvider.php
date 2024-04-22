<?php

namespace TrueRcm\LaravelWebscrape;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
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
            ->hasMigrations([
                '2024_04_17_000002_create_crawl_results_table',
                '2024_04_17_000002_create_crawl_subjects_table',
                '2024_04_17_000002_create_crawl_target_urls_table',
                '2024_04_17_000002_create_crawl_targets_table',
            ])
            ->hasCommand(LaravelWebscrapeCommand::class)
            ->hasInstallCommand(function(InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishAssets()
                    ->publishMigrations()
                    ->askToRunMigrations();
            });
    }
}
