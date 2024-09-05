<?php

use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\Actions\AddCrawlResult;
use TrueRcm\LaravelWebscrape\Actions\StoreCrawlResult;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;
use TrueRcm\LaravelWebscrape\Models\CrawlTargetUrl;

it('will add a crawl result', function () {
    $subject = CrawlSubject::factory()->create(['id' => 111]);
    $crawlTargetUrl = CrawlTargetUrl::factory()->create(['id' => 222]);
    $crawledResult = CrawlResult::factory()->create();

    $storeCrawlResultMock = $this->mock(StoreCrawlResult::class, function (MockInterface $mock) use ($crawledResult) {
        $mock->expects('handle')
            ->once()
            ->with([
                'crawl_subject_id' => 111,
                'crawl_target_url_id' => 222,
                'arg1' => 'x',
                'arg2' => 'z',
            ])
            ->andReturn($crawledResult);
    });

    // $this->instance(StoreCrawlResult::class, $storeCrawlResultMock);

    $result = AddCrawlResult::run($subject, $crawlTargetUrl, ['arg1' => 'x', 'arg2' => 'z']);
    $this->assertInstanceOf(CrawlResult::class, $result);
    $this->assertSame($crawledResult, $result);
});
