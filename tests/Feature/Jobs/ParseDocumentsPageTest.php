<?php

use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Jobs\ParseDocumentsPage;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow('May 17, 2023 2:13 PM');
});

it('will parse the page', function () {
    $html = <<<HTML
<html>
<body>
<div class="e-gridcontent">
<table>
<tr>
<td>
<a href="http:://test.com">Document Link</a>
</td>
<td>
<span>
MA
</span>
</td>
<td>
<span>
12/12/2022
</span>
</td>
<td>
<span>
12/12/2023
</span>
</td>
<td>
<span>
Active
</span>
</td>
</tr>
<tr>
<td>
Extra Row
</td>
</tr>
</table>
</div>
</body>
</html>
HTML;

    $crawlResult = CrawlResult::factory()->create(['body' => $html]);

    $storeCrawlResultMock = $this->mock(UpdateCrawlResult::class, function (MockInterface $mock) use ($crawlResult) {
        $mock->shouldReceive('run')
            ->once()
            ->with($crawlResult, [
                'processed_at' => now(),
                'process_status' => CrawlResultStatus::COMPLETED->value,
                'result' => [
                    "documents" => [
                        [
                            "name" => "Document Link",
                            "link" => "http:://test.com",
                            "state" => "MA",
                            "uploaded_date" => "12/12/2022",
                            "expiration_date" => "12/12/2023",
                            "status" => "Active",
                        ],
                    ],
                ],
            ])
            ->andReturn($crawlResult);
    });

    $this->instance(UpdateCrawlResult::class, $storeCrawlResultMock);

    $job = new ParseDocumentsPage($crawlResult);

    $job->handle();
});


it('will generate error if node not found', function () {

    $html = <<<HTML
<html>
<body>
<div id="abc">
</div>
</body>
</html>
HTML;

    $crawlResult = CrawlResult::factory()->create(['body' => $html, 'url' => 'http:://some.site']);

    $storeCrawlResultMock = $this->mock(UpdateCrawlResult::class, function (MockInterface $mock) use ($crawlResult) {
        $mock->shouldReceive('run')
            ->once()
            ->with($crawlResult, [
                'processed_at' => now(),
                'process_status' => CrawlResultStatus::ERROR->value,
                'result' => ['error' => "Parsing failed for the page with url http:://some.site"],
            ])
            ->andReturn($crawlResult);
    });

    $this->instance(UpdateCrawlResult::class, $storeCrawlResultMock);

    $job = new ParseDocumentsPage($crawlResult);

    $job->handle();
});
