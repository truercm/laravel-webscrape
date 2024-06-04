<?php

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\CrawlTraveller;
use TrueRcm\LaravelWebscrape\Events\CrawlCompleted;
use TrueRcm\LaravelWebscrape\Events\CrawlStarted;
use TrueRcm\LaravelWebscrape\Jobs\CrawlTargetJob;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;
use TrueRcm\LaravelWebscrape\Pipes\AuthenticateBrowser;
use TrueRcm\LaravelWebscrape\Pipes\CrawlPages;
use TrueRcm\LaravelWebscrape\Pipes\ParsePages;
use TrueRcm\LaravelWebscrape\Pipes\ProcessParsingResults;

it('can send traveller through pipelines', function () {
    Event::fake();

    $subject = CrawlSubject::factory()->create(['id' => 111]);
    $traveller = resolve(CrawlTraveller::class, ['subject' => $subject]);
    $this->app->singleton(CrawlTraveller::class, fn () => $traveller);

    $pipeline = $this->mock(Pipeline::class, function (MockInterface $mock) use ($traveller) {
        $mock->expects('send')
            ->with($traveller)
            ->andReturnSelf()
            ->shouldReceive('through')
            ->with([
                AuthenticateBrowser::class,
                CrawlPages::class,
                ParsePages::class,
                ProcessParsingResults::class,
            ])
            ->andReturnSelf();

        $mock->expects('then')->andReturnUsing(fn ($next) => $next($traveller));
    });

    $job = new CrawlTargetJob($subject);

    $job->handle($pipeline);

    Event::assertDispatched(
        CrawlStarted::class,
        fn (CrawlStarted $event) => $subject->is($event->subject));

    Event::assertDispatched(
        CrawlCompleted::class,
        fn (CrawlCompleted $event) => $subject->is($event->subject));
});
