<?php

use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Jobs\ParsePracticeLocationPage;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;

beforeEach(function () {
    Carbon::setTestNow('May 17, 2023 2:13 PM');
});

it('will parse the page', closure: function () {
    $html = <<<HTML
<html>
<body>
<div id="healthplanPLGrid">
<div class="e-gridcontent">
<tr>
<td>
<div class="grid-name-text">BOSTON UNIVERSITY GENERAL SURGICAL ASSOCIATES, INC.  </div>
<div class="grid-taxid-text">
Tax Id:
<span> 043265008 </span>
</div>
</td>
<td>
<div class="address-location">
<div class="grid-name-text">
<div>88 East Newton St, H2707 </div>
<div>Boston, MA 02118</div>
</div>
</div>
</td>
<td>
<p id="pg-tooltiptext">This location has a different Tax ID.</p>
</td>
<td>
<label id="lbl-text-color">1450 Days</label>
</td>
<td></td>
</tr>
</div>
</div>
<div id="divActivePracticeLocation">
<div class="divinnerrow">
<div class="divname">
<ul>
<li>Hathorne Hill</li>
<li>Tax ID:</li>
<li>83-3973422<br></li>
</ul>
</div>
<div class="divaddress">
<p id="practiceLocationAddress">15 Kirkbride Dr<br>Danvers, MA<br>01923-6011</p>
</div>
<div class="divaffiliation">
<p id="practiceAffiliation">I see patients by appointment</p>
</div>
<div class="divlastconfirmeddate">
<label>3/26/2024</label>
</div>
<div class="divmanagedby">
<span>Mihir</span>
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
                    "new_locations" => [
                        [
                            "name" => "BOSTON UNIVERSITY GENERAL SURGICAL ASSOCIATES, INC.",
                            "tax_id" => "043265008",
                            "address" => [
                                "88 East Newton St, H2707 ",
                                "Boston, MA 02118",
                            ],
                            "notes" => "This location has a different Tax ID.",
                            "days_elapsed" => "1450 Days",
                          ],
                    ],
                    "active_locations" => [
                            [
                                "name" => "Hathorne Hill",
                                "tax_id" => "83-3973422",
                                "address" => "15 Kirkbride DrDanvers, MA01923-6011",
                                "affiliation_description" => "I see patients by appointment",
                                "last_confirmed_date" => "3/26/2024",
                                "managed_by" => "Mihir",
                            ],
                    ],
                ],
            ])
            ->andReturn($crawlResult);
    });

    $this->instance(UpdateCrawlResult::class, $storeCrawlResultMock);

    $job = new ParsePracticeLocationPage($crawlResult);

    $job->handle();
});

it('will generate error if node not found', function () {
    $html = <<<HTML
<html>
<body>
<div id="healthplanPLGrid">
<div class="e-gridcontent">
<tr>
<td>
<div class="grid-name-text">BOSTON UNIVERSITY GENERAL SURGICAL ASSOCIATES, INC.  </div>
<div class="grid-taxid-text">
Tax Id:
<span> 043265008 </span>
</div>
</td>
</tr>
</div>
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

    $job = new ParsePracticeLocationPage($crawlResult);

    $job->handle();
});
