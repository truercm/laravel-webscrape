<?php

use TrueRcm\LaravelWebscrape\Exceptions\CrawlException;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;
use TrueRcm\LaravelWebscrape\Pipes\AuthenticateBrowser;
use TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller;
use Illuminate\Support\Facades\Event;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\BrowserKit\Response;
use Mockery\MockInterface;

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

    $this->mock(HttpBrowser::class, function (MockInterface $mock) use ($crawler) {
        $mock->expects('request')
            ->with('GET', 'http:://authenticate.test')
            ->andReturn($crawler);

        $mock->expects('getResponse')->times(3)->andReturn(new Response());
        $mock->expects('submitForm')->with('submit burron', ['a' => 1, 'b' => 2])->andReturn($crawler);
    });

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

    app(AuthenticateBrowser::class)
        ->handle($stub, function (CrawlTraveller $traveller) use ($stub) {
            $this->assertSame($stub, $traveller);
        });
});

it('will throw exception if response is not success', function () {
    $this->expectException(CrawlException::class);

    $html = <<<HTML
<html>
<body>
<input type="text" name="myField" value="This is some text">
</body>
</html>
HTML;

    $crawler = new Crawler($html, 'http:://foo.com');

    $this->mock(HttpBrowser::class, function (MockInterface $mock) use ($crawler) {
        $mock->expects('request')
            ->with('GET', 'http:://authenticate.test')
            ->andReturn($crawler);

        $mock->expects('getResponse')->times(2)->andReturn(new Response('', 300, []));
    });

    $subject = CrawlSubject::factory()->create(['id' => 111]);

    $stub = $this->mock(CrawlTraveller::class, function (MockInterface $mock) use ($subject) {
        $mock->expects('authUrl')
            ->times(2)
            ->andReturn('http:://authenticate.test');
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

    $this->mock(HttpBrowser::class, function (MockInterface $mock) use ($crawler) {
        $mock->expects('request')
            ->with('GET', 'http:://authenticate.test')
            ->andReturn($crawler);

        $mock->expects('getResponse')
            ->times(3)
            ->andReturn(new Response());

        $mock->expects('submitForm')
            ->with('submit burron', ['a' => 1, 'b' => 2])
            ->andReturn($crawler);
    });

    $subject = CrawlSubject::factory()->create(['id' => 111]);

    $stub = $this->mock(CrawlTraveller::class, function (MockInterface $mock) use ($subject) {
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
