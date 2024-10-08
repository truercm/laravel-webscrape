<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\Contracts\BrowserClient;
use TrueRcm\LaravelWebscrape\CrawlTraveller;
use TrueRcm\LaravelWebscrape\Exceptions\CrawlException;
use TrueRcm\LaravelWebscrape\Jobs\CrawlTargetJob;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;

it('can create a crawl traveller', function () {
    $subject = CrawlSubject::factory()->make();
    $traveller = CrawlTraveller::make($subject);
    $this->assertInstanceOf(CrawlTraveller::class, $traveller);
});

it('can start the job from traveller', function ($subject) {
    Bus::fake();
    $traveller = CrawlTraveller::make($subject);
    $traveller->start();
    Bus::assertDispatched(CrawlTargetJob::class);
})->with('subject');

it('can set and get browser on traveller', function () {
    $traveller = resolve(CrawlTraveller::class);
    $browser = $this->mock(BrowserClient::class);
    $traveller->setBrowser($browser);
    $this->assertSame($browser, $traveller->getBrowser());
});

it('can get target urls from traveller', function ($subject) {
    $traveller = CrawlTraveller::make($subject);

    $this->assertCount(3, $traveller->targets());
    $this->assertInstanceOf(Collection::class, $traveller->targets());
})->with('subject');

it('can get auth url from traveller', function ($subject) {
    $traveller = CrawlTraveller::make($subject);

    $this->assertEquals('https://xerox.com/Login/Index', $traveller->authUrl());
})->with('subject');

it('can get auth button text from traveller', function ($subject) {
    $traveller = CrawlTraveller::make($subject);

    $this->assertEquals('Login now', $traveller->authButtonIdentifier());
})->with('subject');

it('can get credentials from traveller', function ($subject) {
    $traveller = CrawlTraveller::make($subject);

    $this->assertEquals([
        'UserName' => 'alfa',
        'Password' => '123456',
    ], $traveller->getCrawlingCredentials());
})->with('subject');

it('can add crawl result page to traveller', function () {
    $traveller = resolve(CrawlTraveller::class);

    $result = CrawlResult::factory()->create();
    $stub = $traveller->addCrawledPage($result);

    $this->assertSame($traveller, $stub);
    $this->assertInstanceOf(Collection::class, $traveller->getCrawledPages());
});

it('can clear the browser in traveller', function () {
    $this->expectException(CrawlException::class);

    $traveller = resolve(CrawlTraveller::class);

    $browser = $this->mock(BrowserClient::class, function (MockInterface $mock) {
        $mock->shouldReceive('quit')
            ->andReturnNull();
    });

    $traveller->setBrowser($browser);

    $this->assertSame($browser, $traveller->getBrowser());

    $stub = $traveller->clearBrowser();

    $this->assertSame($traveller, $stub);
    $this->assertNull($traveller->getBrowser());
});

dataset('subject', [
    fn () => TrueRcm\LaravelWebscrape\Models\CrawlSubject::factory()
        ->for(TrueRcm\LaravelWebscrape\Models\CrawlTarget::factory()
            ->has(TrueRcm\LaravelWebscrape\Models\CrawlTargetUrl::factory()->sequence(
                [
                    'url_template' => 'https://smoodle.com',
                    'handler' => 'Handler1',
                    'result_fields' => [
                        'name',
                        'gender',
                    ],
                ],
                [
                    'url_template' => 'https://foodle.com',
                    'handler' => 'Handler2',
                    'result_fields' => [
                        'medicaid',
                        'medicare',
                    ],
                ],
                [
                    'url_template' => 'https://berry.com',
                    'handler' => 'Handler3',
                ],
            )->count(3), 'crawlTargetUrls', 3)
            ->create([
                'auth_url' => 'https://xerox.com/Login/Index',
                'crawling_job' => CrawlTargetJob::class,
                'auth_button_text' => 'Login now',
            ])
        )
        ->create([
            'credentials' => [
                'UserName' => 'alfa',
                'Password' => '123456',
            ],
            'authenticated_at' => null,
        ]),
]);
