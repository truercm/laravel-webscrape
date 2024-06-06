<?php

use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Jobs\ParseProfessionalLiabilityPage;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;

beforeEach(function () {
    Carbon::setTestNow('May 17, 2023 2:13 PM');
});

it('will parse the page', function () {
    $html = <<<HTML
<html>
<body>
<div id="SummaryPageGridEditRecord_123">
<div class="grid-inner">
<p class="grid-header gird-col1">Bridgeway Insurance Company</p>
</div>
<div class="grid-inner">
<p class="grid-header gird-col2"><span class="grid-header gird-col2">Policy Number :</span>001MA000014641</p>
<label class="control-label" for="Current_Effective_Date">Current Effective Date</label>: 3/1/2023<br>
<text>
<label class="control-label" for="Current_Expiration_Date">Current Expiration Date</label>: 6/30/2024
</text>

<div class="grid-help" id="policyInfoMsg">
<i class="icon-exclamation-sign mr5" aria-hidden="true"></i>This policy will expire before your next attestation.
</div>
</div>
</div>
<input name="IsFTCACovered" type="checkbox" checked="checked" value="true">
<input name="NotInsured" type="checkbox" checked="checked" value="true">
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
                    'current_insurance_policies' => [
                        [
                            'id' => '123',
                            'insurance_company__name' => 'Bridgeway Insurance Company',
                            'policy_number' => '001MA000014641',
                            'current_effective_date' => '3/1/2023',
                            'current_expiration_date' => '6/30/2024',
                            'policy_info_msg' => 'This policy will expire before your next attestation.',
                        ],
                    ],
                    'is_ftca_covered' => true,
                    'is_insured' => false,
                ],
            ])
            ->andReturn($crawlResult);
    });

    $this->instance(UpdateCrawlResult::class, $storeCrawlResultMock);

    $job = new ParseProfessionalLiabilityPage($crawlResult);

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
                'result' => ['error' => 'Parsing failed for the page with url http:://some.site'],
            ])
            ->andReturn($crawlResult);
    });

    $this->instance(UpdateCrawlResult::class, $storeCrawlResultMock);

    $job = new ParseProfessionalLiabilityPage($crawlResult);

    $job->handle();
});
