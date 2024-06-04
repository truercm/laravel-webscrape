<?php

use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Panther\Client;
use TrueRcm\LaravelWebscrape\Contracts\BrowserClient;
use TrueRcm\LaravelWebscrape\Exceptions\CrawlException;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;
use TrueRcm\LaravelWebscrape\Pipes\AuthenticateBrowser;
use TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller;

beforeEach(function () {
    // BypassFinals::enable();
});

it('can authenticate the remote url', function () {
    Event::fake();

    $html = <<<HTML
<html>
<body>
<input type="text" name="myField" value="This is some text">
</body>
</html>
HTML;

    $crawler = new Crawler($html, 'http:://foo.com');

    $subject = CrawlSubject::factory()
        ->hasCrawlTarget(['auth_url' => 'http:://authenticate.test'])
        ->create(['id' => 111]);

    $stub = new CrawlTraveller($subject);

    $this->mock(BrowserClient::class, function (MockInterface $mock) use ($crawler) {
        $mock->shouldReceive('request')
            ->andReturn($crawler);

        $mock->shouldReceive('submitForm')
            ->andReturn($crawler);
    });

    app(AuthenticateBrowser::class)
        ->handle($stub, function (CrawlTraveller $traveller) use ($stub) {
            $this->assertSame($stub, $traveller);
        });
});

it('will throw exception if invalid credential', function () {
    $this->expectException(CrawlException::class);

    $html = <<<HTML
<html>
<body>
<input type="text" name="myField" value="This is some text">
</body>
</html>
HTML;

    $crawler = new Crawler($html, 'http:://authenticate.test');

    $this->partialMock(Client::class, function (MockInterface $mock) use ($crawler) {
        $mock->expects('request')
            ->with('GET', 'http:://authenticate.test')
            ->andReturn($crawler);

        $mock->expects('submitForm')
            ->with('submit button', ['a' => 1, 'b' => 2])
            ->andReturn($crawler);
    });

    $subject = CrawlSubject::factory()->create(['id' => 111]);

    $stub = $this->mock(CrawlTraveller::class, function (MockInterface $mock) {
        $mock->expects('authUrl')
            ->times(3)
            ->andReturn('http:://authenticate.test');

        $mock->expects('authButtonIdentifier')
            ->andReturn('submit button');

        $mock->expects('getCrawlingCredentials')
            ->andReturn(['a' => 1, 'b' => 2]);
    });

    app(AuthenticateBrowser::class)
        ->handle($stub, function (CrawlTraveller $traveller) use ($stub) {
            $this->assertSame($stub, $traveller);
        });
});
