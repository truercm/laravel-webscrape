<?php

use TrueRcm\LaravelWebscrape\Actions\StoreCrawlTargetUrl;
use TrueRcm\LaravelWebscrape\Models\CrawlTargetUrl;

it('will persist a crawl result', function () {
    $attributes = [
        'crawl_target_id' => 111,
        'url_template' => 'http://some.site/profile',
        'handler' => '\TrueRcm\LaravelWebscrape\Tests\Fixtures\FixtureJob',
        'result_fields' => [
            'name',
            'email',
        ],
    ];

    $result = StoreCrawlTargetUrl::run($attributes);

    $this->assertInstanceOf(CrawlTargetUrl::class, $result);
    $this->assertDatabaseCount('crawl_target_urls', 1);
    $this->assertDatabaseHas('crawl_target_urls', array_merge($attributes, ['result_fields' => json_encode(['name', 'email'])]));
});

it('will return consistent validation rule', function () {
    $stub = StoreCrawlTargetUrl::make();

    $this->assertEquals([
        'crawl_target_id' => 'required|numeric',
        'url_template' => 'required|url|unique:crawl_targets,url',
        'handler' => 'required|string|max:255',
        'result_fields' => 'nullable|array',
    ], $stub->rules());
});
