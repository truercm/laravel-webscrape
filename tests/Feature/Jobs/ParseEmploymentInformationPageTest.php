<?php

use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Jobs\ParseEmploymentInformationPage;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;

beforeEach(function () {
    Carbon::setTestNow('May 17, 2023 2:13 PM');
});

it('will parse the page', closure: function () {
    $html = <<<HTML
<html>
<body>
<div id="Employment-Edit-Records">
<div id="SummaryPageGridEditRecord_123">
<div class="grid-inner">
<p> COMPREHENSIVE REHAB CONSULTANTS PLLC</p>
</div>
<div class="grid-inner">
<p>
July 2023 -
<span>Current Employment</span>
</p>
</div>
</div>
<div id="SummaryPageGridEditRecord_456">
<div class="grid-inner">
<p> MU INC</p>
</div>
<div class="grid-inner">
<p>
March 2020 -
September 2023
</p>
</div>
</div>

<input name=".HaveYouEverServedInTheUSMilitary" type="radio" value="100000000" checked="checked">
<input name=".HaveYouEverServedInTheUSMilitary" type="radio" value="100000001">

<input name=".AreYouCurrentlyOnActivemilitaryDuty" type="radio" value="100000000" checked="checked">
<input name=".AreYouCurrentlyOnActivemilitaryDuty" type="radio" value="100000001">

<input name=".ReservesorNationalGuard" type="radio" value="100000000" checked="checked">
<input name=".ReservesorNationalGuard" type="radio" value="100000001">
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
                    "employment_records" => [
                        [
                            "id" => "123",
                            "company_name" => "COMPREHENSIVE REHAB CONSULTANTS PLLC",
                            "from" => "July 2023",
                            "to" => null,
                            "currently_epmloyed" => true,
                        ],
                        [
                            "id" => "456",
                            "company_name" => "MU INC",
                            "from" => "March 2020",
                            "to" => "September 2023",
                            "currently_epmloyed" => false,
                        ],
                    ],
                    "Have you ever served or are you currently serving in the United States Military?" => true,
                    "Are you currently on active military duty??" => true,
                    "Are you currently in the Reserves or National Guard?" => true,
                ],
            ])
            ->andReturn($crawlResult);
    });

    $this->instance(UpdateCrawlResult::class, $storeCrawlResultMock);

    $job = new ParseEmploymentInformationPage($crawlResult);

    $job->handle();
});

it('will generate error if node not found', function () {
    $html = <<<HTML
<html>
<body>
<div id="SummaryPageGridEditRecord_456">
</div>
</body>
</html>
HTML;

    $crawlResult = CrawlResult::factory()->create(['body' => $html, 'url' => 'http:://some.site']);

    $storeCrawlResultMock = $this->mock(UpdateCrawlResult::class, function (MockInterface $mock) use ($crawlResult) {
        $mock->shouldReceive('run')
            ->once()
            ->andReturn($crawlResult);
    });

    $this->instance(UpdateCrawlResult::class, $storeCrawlResultMock);

    $job = new ParseEmploymentInformationPage($crawlResult);

    $job->handle();
});
