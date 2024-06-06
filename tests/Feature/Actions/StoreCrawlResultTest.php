<?php

use TrueRcm\LaravelWebscrape\Actions\StoreCrawlResult;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;

it('will persist a crawl result', function () {
    $attributes = [
        'crawl_target_url_id' => 1,
        'crawl_subject_id' => 2,
        'url' => 'http:://some.site',
        'handler' => '\TrueRcm\LaravelWebscrape\Tests\Fixtures\FixtureJob',
        'status' => 200,
        'body' => 'some text',
        'processed_at' => '2022-11-09 18:00:00',
        'process_status' => 'pending',
        'result' => ['name' => 'Amit']
    ];

    $result = StoreCrawlResult::run($attributes);

    $this->assertInstanceOf(CrawlResult::class, $result);
    $this->assertDatabaseCount('crawl_results', 1);
    $this->assertDatabaseHas('crawl_results', array_merge($attributes, ['result' => json_encode(['name' => 'Amit'])]));
});

it('will return consistent validation rule', function () {
    $stub = StoreCrawlResult::make();

    $this->assertEquals([
        'crawl_target_url_id' => ['required'],
        'crawl_subject_id' => ['required'],
        'url' => ['required'],
        'handler' => ['required'],
        'status' => ['required'],
        'body' => ['nullable'],
        'processed_at' => ['nullable', 'date'],
        'process_status' => ['nullable', 'string'],
        'result' => ['nullable', 'array'],
    ], $stub->rules());
});
