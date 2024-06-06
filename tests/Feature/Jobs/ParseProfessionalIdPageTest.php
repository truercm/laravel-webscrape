<?php

use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Jobs\ParseProfessionalIdPage;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;

beforeEach(function () {
    Carbon::setTestNow('May 17, 2023 2:13 PM');
});

it('will parse the page', function () {
    $html = <<<HTML
<html>
<body>
<div id="ProfessionLicenseDetails">
<div class="e-gridcontent">
<tr>
<td>111</td>
<td>MA</td>
<td>Yes</td>
<td>PA123</td>
<td>12/11/2023</td>
</tr>
</div>
</div>
<div id="CDSDetails">
<div class="e-gridcontent">
<tr>
<td>111</td>
<td>DA</td>
<td>CD123</td>
<td>12/11/2021</td>
<td>12/11/2023</td>
</tr>
</div>
</div>
<div id="DEARegistrationSection"></div>
<div id="MedicaidPlaceHolder">
<div class="collection-item">
<input name=".Number" value="7654">
<select name=".StateId">
<option value="" >Delhi</option>
<option value="" selected="selected">UP</option>
</select>
</div>
</div>
<div id="MedicarePlaceHolder">
<div class="collection-item">
<input name=".Number" value="1543">
<select name=".StateId">
<option value="" selected="selected">Delhi</option>
<option value="" >UP</option>
</select>
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
                    'licenses' => [
                        [
                            'state' => 'MA',
                            'current' => 'Yes',
                            'number' => 'PA123',
                            'expires_at' => '12/11/2023',
                        ],
                    ],
                    'cds' => [
                        [
                            'state' => 'DA',
                            'number' => 'CD123',
                            'issued_at' => '12/11/2021',
                            'expires_at' => '12/11/2023',
                        ],
                    ],
                    'medicaids' => [
                        [
                            'number' => '7654',
                            'state' => 'UP',
                        ],
                    ],
                    'medicares' => [
                        [
                            'number' => '1543',
                            'state' => 'Delhi',
                        ],
                    ],
                ],
            ])
            ->andReturn($crawlResult);
    });

    $this->instance(UpdateCrawlResult::class, $storeCrawlResultMock);

    $job = new ParseProfessionalIdPage($crawlResult);

    $job->handle();
});

it('will generate error if node not found', function () {
    $html = <<<HTML
<html>
<body>
<div id="ProfessionLicenseDetails">
<div class="e-gridcontent">
<tr>
<td>111</td>
<td>MA</td>
<td>Yes</td>

</tr>
</div>
</div>
<div id="CDSDetails">
<div class="e-gridcontent">
<tr>
<td>111</td>
<td>DA</td>
<td>CD123</td>

</tr>
</div>
</div>
<div id="DEARegistrationSection"></div>
<div id="MedicaidPlaceHolder">
<div class="collection-item">

</div>
</div>
<div id="MedicarePlaceHolder">
<div class="collection-item">

</div>
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

    $job = new ParseProfessionalIdPage($crawlResult);

    $job->handle();
});
