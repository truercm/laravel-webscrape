<?php

use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;

it('will update a crawl result', function () {
    $crawlResult = CrawlResult::factory()->create(['id' => 111]);

    $attributes = [
        'crawl_target_url_id' => 1,
        'crawl_subject_id' => 2,
        'url' => 'http:://some.site',
        'handler' => '\TrueRcm\LaravelWebscrape\Tests\Fixtures\FixtureJob',
        'status' => 200,
        'body' => 'some text',
        'processed_at' => 'November 9, 2022 6:00 PM',
        'process_status' => 'pending',
        'result' => ['name' => 'Amit'],
    ];

    $result = UpdateCrawlResult::run($crawlResult, $attributes);

    $this->assertInstanceOf(CrawlResult::class, $result);
    $this->assertTrue($crawlResult->is($result));
    $this->assertDatabaseCount('crawl_results', 1);
    $this->assertDatabaseHas('crawl_results', [
        'crawl_target_url_id' => 1,
        'crawl_subject_id' => 2,
        'url' => 'http:://some.site',
        'handler' => '\TrueRcm\LaravelWebscrape\Tests\Fixtures\FixtureJob',
        'status' => 200,
        'body' => 'some text',
        'processed_at' => 'November 9, 2022 6:00 PM',
        'process_status' => 'pending',
        'result' => json_encode(['name' => 'Amit']),
    ]);
});

it('will return consistent validation rule', function () {
    $stub = UpdateCrawlResult::make();

    $this->assertEquals([
        'crawl_target_url_id' => ['sometimes', 'required'],
        'crawl_subject_id' => ['sometimes', 'required'],
        'url' => ['sometimes', 'required'],
        'handler' => ['sometimes', 'required'],
        'status' => ['sometimes', 'required'],
        'body' => ['nullable'],
        'processed_at' => ['nullable', 'date'],
        'process_status' => ['nullable', 'string'],
        'result' => ['nullable', 'array'],
    ], $stub->rules());
});
