<?php

namespace TrueRcm\LaravelWebscrape\Commands;

use Illuminate\Console\Command;

class LaravelWebscrapeCommand extends Command
{
    public $signature = 'laravel-webscrape';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
