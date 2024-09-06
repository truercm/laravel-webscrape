<?php

use Illuminate\Bus\PendingBatch;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\CrawlTraveller;
use TrueRcm\LaravelWebscrape\Events\CrawlCompleted;
use TrueRcm\LaravelWebscrape\Events\CrawlStarted;
use TrueRcm\LaravelWebscrape\Jobs\CrawlTargetJob;
use TrueRcm\LaravelWebscrape\Jobs\ParseCrawledPage;
use TrueRcm\LaravelWebscrape\Jobs\ProcessParsedResultsJob;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;
use TrueRcm\LaravelWebscrape\Pipes\AuthenticateBrowser;
use TrueRcm\LaravelWebscrape\Pipes\CloseBrowser;
use TrueRcm\LaravelWebscrape\Pipes\CrawlPages;

it('can send traveller through pipelines', function () {
    Event::fake();
    Bus::fake();

    $subject = CrawlSubject::factory()->create(['id' => 111]);

    $crawledResults = CrawlResult::factory()
        ->count(2)
        ->create();

    $traveller = $this->mock(CrawlTraveller::class, function (MockInterface $mock) use ($crawledResults, $subject) {
        $mock->expects('subject')
            ->times(2)
            ->andReturn($subject);

        $mock->expects('getCrawledPages')
            ->andReturn($crawledResults);
    });

    $pipeline = $this->mock(Pipeline::class, function (MockInterface $mock) use ($traveller) {
        $mock->expects('send')
            ->with($traveller)
            ->andReturnSelf();

        $mock
            ->expects('through')
            ->with([
                AuthenticateBrowser::class,
                CrawlPages::class,
                CloseBrowser::class,
            ])
            ->andReturnSelf();

        $mock
            ->expects('then')
            ->andReturnUsing(fn ($next) => $next($traveller));
    });

    $job = new CrawlTargetJob($traveller);

    $job->handle($pipeline);

    Bus::assertBatched(function (PendingBatch  $batch) use($subject){
        $this->assertCount(2, $batch->jobs);
        $batch->jobs->each(fn($job) => $this->assertInstanceOf(ParseCrawledPage::class, $job));

        /* @var \Illuminate\Queue\SerializableClosure $thenCallback */
        [$thenCallback] = $batch->thenCallbacks();
        [$finallyCallback] = $batch->finallyCallbacks();


        $thenCallback->getClosure()->call($this, $this);
        $finallyCallback->getClosure()->call($this, $this);

        Bus::assertDispatched(ProcessParsedResultsJob::class, 1);
        Event::assertDispatched(
            CrawlCompleted::class,
            fn (CrawlCompleted $event) => $subject->is($event->subject));

        return true;
    });

    Event::assertDispatched(
        CrawlStarted::class,
        fn (CrawlStarted $event) => $subject->is($event->subject));

});
