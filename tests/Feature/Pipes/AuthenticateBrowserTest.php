<?php

use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Symfony\Component\DomCrawler\Crawler;
use TrueRcm\LaravelWebscrape\Contracts\BrowserClient;
use TrueRcm\LaravelWebscrape\CrawlTraveller;
use TrueRcm\LaravelWebscrape\Exceptions\CrawlException;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;
use TrueRcm\LaravelWebscrape\Models\CrawlTarget;
use TrueRcm\LaravelWebscrape\Pipes\AuthenticateBrowser;

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

    $this->mock(BrowserClient::class, function (MockInterface $mock) use ($crawler) {
        $mock->shouldReceive('request')
            ->andReturn($crawler);

        $mock->shouldReceive('submitForm')
            ->andReturn($crawler);
    });

    $subject = CrawlSubject::factory()
        ->for(CrawlTarget::factory()->create([
            'auth_button_text' => 'Sign In',
            'auth_url' => 'http:://authenticate.test',
            ])
        )
        ->create(['id' => 111]);

    $stub = new CrawlTraveller($subject);

    app(AuthenticateBrowser::class)
        ->handle($stub, function (CrawlTraveller $traveller) use ($stub) {
            $this->assertSame($stub, $traveller);
        });
});
