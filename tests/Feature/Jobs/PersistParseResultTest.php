<?php

namespace TrueRcm\LaravelWebscrape\Tests\Feature\Jobs;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Exceptions\CrawlException;
use TrueRcm\LaravelWebscrape\Jobs\PersistParseResult;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;

it('runs UpdateCrawlResult with expected arguments and clears cache', function () {
    Log::spy();
    Carbon::setTestNow('May 17, 2023 2:13 PM');
    config(['app.name' => 'My Test Site']);

    Log::shouldReceive('info')->times(2);

    $crawlResult = CrawlResult::factory()->create(['id'=>111]);
    Cache::put('my_test_site:App.CrawlResult.111.parsed', [
        'some' => 'data'
    ]);

    $this->assertTrue(Cache::has('my_test_site:App.CrawlResult.111.parsed'));

    $this->mock(UpdateCrawlResult::class, function (MockInterface $mock) use ($crawlResult) {
        $mock->expects('handle')
            ->once()
            ->with(
                Mockery::on(fn($arg) => $arg->is($crawlResult)), // same DB row
                [
                    'processed_at' => now(),
                    'process_status' => CrawlResultStatus::COMPLETED,
                    'result' => [
                        'some' => 'data'
                    ]
                ]
            )
            ->andReturn($crawlResult);
    });

    $job = new PersistParseResult(111);
    $job->handle();

    $this->assertFalse(Cache::has('my_test_site:App.CrawlResult.111.parsed'));
});

it('will throw exception when crawl result not found', function () {
    Log::spy();
    $this->expectException(CrawlException::class);

    $job = new PersistParseResult(111);

    $job->handle();

    Log::shouldHaveReceived('error')->withArgs(function ($msg, $context) {
        return str_contains($msg, 'CrawlResult not found for ID 111');
    });
});

it('generates correct cache key', function () {
    config(['app.name' => 'My Test Site']);
    CrawlResult::factory()->create(['id'=>111]);

    $job = new PersistParseResult(111);
    $job->handle();

    $this->assertSame($job->cacheKey(), "my_test_site:App.CrawlResult.111.parsed");
});
