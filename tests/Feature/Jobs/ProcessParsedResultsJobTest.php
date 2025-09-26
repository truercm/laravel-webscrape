<?php

namespace TrueRcm\LaravelWebscrape\Tests\Feature\Jobs;

use Mockery;
use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\Actions\ParseFinalResult;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlSubject;
use TrueRcm\LaravelWebscrape\Jobs\ProcessParsedResultsJob;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;
use TrueRcm\LaravelWebscrape\Tests\TestCase;

class ProcessParsedResultsJobTest extends TestCase
{
    /** @test */
    public function it_will_handle_parse_final_result_job(): void
    {
        $subject = CrawlSubject::factory()->create(['id' => 111]);

        $crawledResults = CrawlResult::factory()
            ->sequence(
                [
                    'id' => 222,
                ],
                [
                    'id' => 333,
                ]
            )
            ->count(2)
            ->create();

        $this->mock(ParseFinalResult::class, function (MockInterface $mock) use ($crawledResults) {
            $mock->expects('handle')
                ->once()
                ->with(Mockery::on(function ($arg) use ($crawledResults) {
                    return $arg->count() === $crawledResults->count()
                        && $arg->pluck('id')->diff($crawledResults->pluck('id'))->isEmpty();
                }))
                ->andReturn(collect([['name' => 'abc'],['email' => 'test@test.com']]));
        });

        $this->mock(UpdateCrawlSubject::class, function (MockInterface $mock) use ($subject) {
            $mock->expects('handle')
                ->once()
                ->with(
                    Mockery::on(fn($arg) => $arg->is($subject)), // same DB row
                    ['result' => [
                        "name" => "abc",
                        "email" => "test@test.com",
                    ]]
                )
                ->andReturn($subject);
        });

        $job = new ProcessParsedResultsJob(111, collect([222, 333]));

        $job->handle();
    }
}
