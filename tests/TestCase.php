<?php

namespace TrueRcm\LaravelWebscrape\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use TrueRcm\LaravelWebscrape\LaravelWebscrapeServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'TrueRcm\\LaravelWebscrape\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelWebscrapeServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        $migrations = [
            __DIR__.'/../database/migrations/create_crawl_results_table.php.stub',
            __DIR__.'/../database/migrations/create_crawl_subjects_table.php.stub',
            __DIR__.'/../database/migrations/create_crawl_target_urls_table.php.stub',
            __DIR__.'/../database/migrations/create_crawl_targets_table.php.stub',
        ];

        foreach ($migrations as $migration) {
            $migration = include $migration;
            $migration->up();
        }
    }
}
