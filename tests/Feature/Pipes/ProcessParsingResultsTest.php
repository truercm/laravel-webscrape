<?php

use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\Actions\ParseFinalResult;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlSubject;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;
use TrueRcm\LaravelWebscrape\Pipes\ProcessParsingResults;
use TrueRcm\LaravelWebscrape\Traveler\CrawlTraveller;

it('will call actions to process final result and update subject', function () {
    $subject = CrawlSubject::factory()->create(['id' => 111]);

    $crawledResults = CrawlResult::factory()
        ->count(2)
        ->create();

    $stub = $this->mock(CrawlTraveller::class, function (MockInterface $mock) use ($crawledResults, $subject) {
        $mock->expects('subject')
            ->andReturn($subject);

        $mock->expects('getCrawledPages')
            ->andReturn($crawledResults);
    });

    $parseFinalResultMock = $this->mock(ParseFinalResult::class, function (MockInterface $mock) use ($crawledResults) {
        $mock->shouldReceive('run')
            ->once()
            ->with($crawledResults)
            ->andReturn(collect([]));
    });

    $updateCrawlSubjectMock = $this->mock(UpdateCrawlSubject::class, function (MockInterface $mock) use ($subject) {
        $mock->shouldReceive('run')
            ->once()
            ->with($subject, ['result' => []])
            ->andReturn($subject);
    });

    $this->instance(ParseFinalResult::class, $parseFinalResultMock);
    $this->instance(UpdateCrawlSubject::class, $updateCrawlSubjectMock);

    app(ProcessParsingResults::class)
        ->handle($stub, function (CrawlTraveller $traveller) use ($stub) {
            $this->assertSame($stub, $traveller);
        });
});
