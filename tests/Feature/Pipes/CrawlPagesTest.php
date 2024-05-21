<?php

use TrueRcm\LaravelWebscrape\Actions\AddCrawlResult;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;
use TrueRcm\LaravelWebscrape\Models\CrawlTargetUrl;
use TrueRcm\LaravelWebscrape\Pipes\CrawlPages;
use TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller;
use Illuminate\Support\Facades\Event;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\BrowserKit\Response;
use Mockery\MockInterface;

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

    $browser = $this->mock(HttpBrowser::class, function (MockInterface $mock) use ($crawler) {
        $mock->expects('request')
            ->with('GET', 'http:://homepage.test')
            ->andReturn($crawler);

        $mock->expects('getResponse')->andReturn(new Response());
    });

    $subject = CrawlSubject::factory()->create(['id' => 111]);
    $crawlResult = CrawlResult::factory()->create(['id' => 111]);
    $targetUrls = CrawlTargetUrl::factory()
        ->count(1)
        ->create()
        ->map(fn(CrawlTargetUrl $crawlTargetUrl) => $crawlTargetUrl->setAttribute('url', 'http:://homepage.test'));

    $stub = $this->mock(CrawlTraveller::class, function (MockInterface $mock) use ($subject, $browser, $crawlResult, $targetUrls) {
        $mock->expects('subject')
            ->andReturn($subject);

        $mock->expects('getBrowser')
            ->times(2)
            ->andReturn($browser);

        $mock->expects('addCrawledPage')
            //->with($crawlResult)
            ->andReturnSelf();

        $mock->expects('targets')
            ->andReturn($targetUrls);

    });

    AddCrawlResult::shouldRun()
        ->andReturn($crawlResult);

    app(CrawlPages::class)
        ->handle($stub, function (CrawlTraveller $traveller) use ($stub) {
            $this->assertSame($stub, $traveller);
        });

});
