<?php

use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DomCrawler\Crawler;
use TrueRcm\LaravelWebscrape\Exceptions\CrawlException;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;
use TrueRcm\LaravelWebscrape\Pipes\AuthenticateBrowser;
use TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller;
use Symfony\Component\Panther\Client;
use DG\BypassFinals;

beforeEach(function () {
    //BypassFinals::enable();
});

it('can authenticate the remote url', function () {
    Event::fake();
    putenv("SELENIUM_DRIVER_URL=null");

    $html = <<<HTML
<html>
<body>
<input type="text" name="myField" value="This is some text">
</body>
</html>
HTML;

    $crawler = new Crawler($html, 'http:://foo.com');



    $subject = CrawlSubject::factory()->create(['id' => 111]);

    $stub = $this->mock(CrawlTraveller::class, function (MockInterface $mock) use ($subject) {
        $mock->expects('authUrl')
            ->times(3)
            ->andReturn('http:://authenticate.test');

        $mock->expects('authButtonIdentifier')
            ->andReturn('submit burron');

        $mock->expects('getCrawlingCredentials')
            ->andReturn(['a' => 1, 'b' => 2]);

        $mock->expects('setBrowser')
            ->andReturnSelf();

        $mock->expects('subject')
            ->andReturn($subject);
    });

    $browser = Client::createSeleniumClient();

    $mock = Mockery::mock($browser);
    $mock->shouldReceive('request')
        ->with('GET', 'http:://authenticate.test')
        ->andReturn($crawler);

    $mock->shouldReceive('submitForm')
        ->with('submit burron', ['a' => 1, 'b' => 2])
        ->andReturn($crawler);

    $clientMock = $this->mock(Client::class, function (MockInterface $mock) use ($crawler, $browser) {

        $mock->expects('request')
            ->with('GET', 'http:://authenticate.test')
            ->andReturn($crawler);

        $mock->expects('submitForm')->with('submit burron', ['a' => 1, 'b' => 2])->andReturn($crawler);
    })->makePartial();

    /*$this->mock(AuthenticateBrowser::class, function (MockInterface $mock) use ($mock) {
        $mock->expects('getBrowser')
            ->andReturn($mock);

    })->makePartial();*/

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
            ->with('submit burron', ['a' => 1, 'b' => 2])
            ->andReturn($crawler);
    });

    $subject = CrawlSubject::factory()->create(['id' => 111]);

    $stub = $this->mock(CrawlTraveller::class, function (MockInterface $mock) {
        $mock->expects('authUrl')
            ->times(3)
            ->andReturn('http:://authenticate.test');

        $mock->expects('authButtonIdentifier')
            ->andReturn('submit burron');

        $mock->expects('getCrawlingCredentials')
            ->andReturn(['a' => 1, 'b' => 2]);
    });

    app(AuthenticateBrowser::class)
        ->handle($stub, function (CrawlTraveller $traveller) use ($stub) {
            $this->assertSame($stub, $traveller);
        });
});
