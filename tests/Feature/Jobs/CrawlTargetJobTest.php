<?php

use Illuminate\Bus\PendingBatch;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Laravel\SerializableClosure\SerializableClosure;
use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlSubject;
use TrueRcm\LaravelWebscrape\CrawlTraveller;
use TrueRcm\LaravelWebscrape\Events\CrawlCompleted;
use TrueRcm\LaravelWebscrape\Events\CrawlFailed;
use TrueRcm\LaravelWebscrape\Events\CrawlStarted;
use TrueRcm\LaravelWebscrape\Jobs\CrawlTargetJob;
use TrueRcm\LaravelWebscrape\Jobs\ParseCrawledPage;
use TrueRcm\LaravelWebscrape\Jobs\PersistParseResult;
use TrueRcm\LaravelWebscrape\Jobs\ProcessParsedResultsJob;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;
use TrueRcm\LaravelWebscrape\Pipes\AuthenticateBrowser;
use TrueRcm\LaravelWebscrape\Pipes\CloseBrowser;
use TrueRcm\LaravelWebscrape\Pipes\CrawlPages;
use TrueRcm\LaravelWebscrape\Tests\Fixtures\SubjectContainerModel;

it('it passes traveller through expected pipeline stages', function () {
    Log::spy();

    $traveller = Mockery::mock(CrawlTraveller::class);
    $subject = CrawlSubject::factory()->create(['id' => 111]);

    $traveller->shouldReceive('subject')->andReturn($subject);
    $traveller->shouldReceive('getCrawledPages')->andReturn(collect());
    $traveller->shouldReceive('clearBrowser')->once();

    // Spy on job to confirm dispatchPostCrawlJobs is called
    $job = Mockery::mock(CrawlTargetJob::class, [$traveller])
        ->makePartial();

    $job->shouldAllowMockingProtectedMethods();
    $job->shouldReceive('dispatchPostCrawlJobs')->once();

    $pipeline = Mockery::mock(Pipeline::class);
    $pipeline->shouldReceive('send')->with($traveller)->andReturnSelf();
    $pipeline->shouldReceive('through')
        ->with([
            AuthenticateBrowser::class,
            CrawlPages::class,
            CloseBrowser::class,
        ])
        ->andReturnSelf();

    $pipeline->shouldReceive('then')
        ->andReturnUsing(function ($callback) use ($traveller) {
            $callback($traveller);
        });

    $job->handle($pipeline);

    Log::shouldHaveReceived('info')->withArgs(function ($msg) {
        return str_contains($msg, 'initiated for subject ID');
    });
});

it('it handles exception thrown from pipeline', function () {
    Bus::fake();
    Event::fake();
    Log::spy();

    $traveller = Mockery::mock(CrawlTraveller::class);
    $subject = CrawlSubject::factory()->create(['id' => 111]);

    $traveller->shouldReceive('subject')->andReturn($subject);
    $traveller->shouldReceive('clearBrowser')->once();

    $pipeline = Mockery::mock(Pipeline::class);
    $pipeline->shouldReceive('send')->andThrow(new \RuntimeException('pipe error'));

    // Expect UpdateCrawlSubject::run to be invoked
    $this->mock(UpdateCrawlSubject::class, function (MockInterface $mock) use ($subject) {
        $mock->expects('handle')
            ->once()
            ->with($subject, [
                'result' => []
            ])
            ->andReturn($subject);
    });

    $job = new CrawlTargetJob($traveller);
    $job->handle($pipeline);

    Event::assertDispatched(CrawlStarted::class);
    Event::assertDispatched(CrawlFailed::class);
    Log::shouldHaveReceived('error')->withArgs(function ($msg, $context) {
        return str_contains($msg, 'Job failed for subject') &&
            $context['error'] === 'pipe error';
    });
});

it('it generates unique job id', function () {
    config(['app.name' => 'My Test Site']);
    SubjectContainerModel::migrate();

    $traveller = Mockery::mock(CrawlTraveller::class);
    $subject = CrawlSubject::factory()
        ->for(SubjectContainerModel::factory()->create(['id' => 201]), 'model')
        ->create(['id' => 111]);

    $traveller->shouldReceive('subject')->andReturn($subject);

    $job = new CrawlTargetJob($traveller);

    $uniqueId = $job->uniqueId();

    $this->assertSame('my_test_site:201', $uniqueId);
});

it('dispatch post crawl jobs creates expected batches', function () {
    Bus::fake();
    Event::fake();
    Log::spy();

    $traveller = Mockery::mock(CrawlTraveller::class);
    $subject = CrawlSubject::factory()->create(['id' => 111]);
    $crawledResults = CrawlResult::factory()
        ->count(2)
        ->create();

    $traveller->shouldReceive('subject')->andReturn($subject);
    $traveller->shouldReceive('getCrawledPages')
        ->andReturn($crawledResults);

    $job = new CrawlTargetJob($traveller);
    // ✅ Call protected method via reflection
    $reflection = new \ReflectionMethod($job, 'dispatchPostCrawlJobs');
    $reflection->setAccessible(true);
    $reflection->invoke($job, $traveller);

    Bus::assertBatched(function (PendingBatch $batch) {
        $jobs = collect($batch->jobs);
        $jobs->contains(fn($job) => $this->assertInstanceOf(ParseCrawledPage::class, $job));

        /* @var \Laravel\SerializableClosure\SerializableClosure $thenCallback */
        [$thenCallback] = $batch->thenCallbacks();
        [$finallyCallback] = $batch->finallyCallbacks();


        $thenCallback->getClosure()->call($batch, $batch);
        $finallyCallback->getClosure()->call($batch, $batch);

        return true;
    });

    Bus::assertBatched(function (PendingBatch $batch) {
        $jobs = collect($batch->jobs);
        return $jobs->contains(fn($j) => $j instanceof PersistParseResult)
            || $jobs->contains(fn($j) => $j instanceof ProcessParsedResultsJob);
    });

    Event::assertDispatched(CrawlCompleted::class);

    Log::shouldHaveReceived('info')->withArgs(function ($msg) {
        return str_contains($msg, 'Pages crawled for subject ID');
    });
    Log::shouldHaveReceived('info')->withArgs(function ($msg) {
        return str_contains($msg, 'batch dispatched for subject ID');
    });
});

it('it can directly invoke failed method', function () {

    Event::fake();
    Log::spy();

    $traveller = Mockery::mock(CrawlTraveller::class);
    $subject = CrawlSubject::factory()->create(['id' => 111]);

    $traveller->shouldReceive('subject')->andReturn($subject);

    // Expect UpdateCrawlSubject::run to be invoked
    $this->mock(UpdateCrawlSubject::class, function (MockInterface $mock) use ($subject) {
        $mock->expects('handle')
            ->once()
            ->with($subject, [
                'result' => []
            ])
            ->andReturn($subject);
    });

    $job = new CrawlTargetJob($traveller);
    $job->failed(new \RuntimeException('boom'));

    Event::assertDispatched(CrawlFailed::class);
    Log::shouldHaveReceived('error')->withArgs(function ($msg, $context) {
        return str_contains($msg, 'Job failed for subject') &&
            $context['error'] === 'boom';
    });
});
