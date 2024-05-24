<?php

use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Jobs\ParseHospitalAffiliationPage;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow('May 17, 2023 2:13 PM');
});

it('will parse the page', function () {
    $html = <<<HTML
<html>
<body>
<div id="edit-admitting-arrangements">
<div id="SummaryPageGridEditRecord_1234">
<div class="grid-inner">
Aman
</div>
<div class="grid-inner">
<p>Completed</p>
<p>Delhi</p>
</div>
</div>
</div>
<div id="edit-admitting-arrangements">
<div id="SummaryPageGridEditRecord_5678">
<div class="grid-inner">
Sumit
</div>
<div class="grid-inner">
<p>Initiated</p>
<p>Noida</p>
</div>
</div>
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
                    'admitting_privileges' => [
                        [
                            'id' => "1234",
                            'name' => "Aman",
                            'status' => "Completed",
                            'location' => "Delhi",
                        ],
                        [
                            'id' => "5678",
                            'name' => "Sumit",
                            'status' => "Initiated",
                            'location' => "Noida",

                        ]
                    ],
                ],
            ])
            ->andReturn($crawlResult);
    });

    $this->instance(UpdateCrawlResult::class, $storeCrawlResultMock);

    $job = new ParseHospitalAffiliationPage($crawlResult);

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

    $job = new ParseHospitalAffiliationPage($crawlResult);

    $job->handle();
});
