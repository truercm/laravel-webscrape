<?php

use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Symfony\Component\DomCrawler\Crawler;
use TrueRcm\LaravelWebscrape\Actions\AddCrawlResult;
use TrueRcm\LaravelWebscrape\Contracts\BrowserClient;
use TrueRcm\LaravelWebscrape\CrawlTraveller;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;
use TrueRcm\LaravelWebscrape\Models\CrawlTarget;
use TrueRcm\LaravelWebscrape\Models\CrawlTargetUrl;
use TrueRcm\LaravelWebscrape\Pipes\CrawlPages;

it('will crawl remote url', function () {
    Event::fake();

    $html = <<<HTML
<html>
<body>
<input type="text" name="myField" value="This is some text">
</body>
</html>
HTML;

    $crawler = new Crawler($html, 'http:://foo.com');

    $mock = $this->mock(BrowserClient::class, function (MockInterface $mock) use ($crawler) {
        $mock->shouldReceive('request')
            ->andReturn($crawler);

        $mock->shouldReceive('waitForInvisibility')
            ->andReturn($crawler);

        $mock->shouldReceive('executeScript')
            ->andReturn(200);
    });

    $subject = CrawlSubject::factory()
        ->for(CrawlTarget::factory()
            ->create([
                'id' => 111,
            ])
        )
        ->create();
    $crawlResult = CrawlResult::factory()->create(['id' => 111]);
    $targetUrl = CrawlTargetUrl::factory()
        ->create([
            'crawl_target_id' => 111,
            'url_template' => 'http:://homepage.test',
            'handler' => 'MyHaandler',
        ]);

    $stub = (new CrawlTraveller($subject))->setBrowser($mock);

    $addCrawlResultMock = $this->mock(AddCrawlResult::class, function (MockInterface $mock) use ($crawlResult) {
        $mock->expects('handle')
            ->once()
            ->andReturn($crawlResult);
    });

    app(CrawlPages::class)
        ->handle($stub, function (CrawlTraveller $traveller) use ($stub) {
            $this->assertSame($stub, $traveller);
        });

    $this->assertCount(1, $stub->getCrawledPages());
    $this->assertTrue($stub->getCrawledPages()->contains($crawlResult));
});
