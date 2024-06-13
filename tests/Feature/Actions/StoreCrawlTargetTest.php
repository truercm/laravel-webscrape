<?php

use TrueRcm\LaravelWebscrape\Actions\StoreCrawlTarget;
use TrueRcm\LaravelWebscrape\Models\CrawlTarget;

it('will persist a crawl result', function () {
    $attributes = [
        'url' => 'http://some.site',
        'name' => 'FOO',
        'auth_url' => 'http://some.site/login',
        'auth_button_text' => 'Sign In',
        'crawling_job' => '\TrueRcm\LaravelWebscrape\Tests\Fixtures\FixtureJob',
    ];

    $result = StoreCrawlTarget::run($attributes);

    $this->assertInstanceOf(CrawlTarget::class, $result);
    $this->assertDatabaseCount('crawl_targets', 1);
    $this->assertDatabaseHas('crawl_targets', $attributes);
});

it('will return consistent validation rule', function () {
    $stub = StoreCrawlTarget::make();

    $this->assertEquals([
        'url' => 'required|url|unique:crawl_targets,url',
        'name' => 'required|string|max:255',
        'auth_url' => 'required|url',
        'auth_button_text' => 'required|string|max:255',
        'crawling_job' => 'string|max:255',
    ], $stub->rules());
});
