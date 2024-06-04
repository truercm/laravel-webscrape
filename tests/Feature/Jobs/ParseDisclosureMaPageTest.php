<?php

use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Jobs\ParseDisclosureMaPage;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;

beforeEach(function () {
    Carbon::setTestNow('May 17, 2023 2:13 PM');
});

it('will parse the page', function () {
    $html = <<<HTML
<html>
<body>
<div class="form-main">
<div class="form-section">
<p class="section-title">Section 1</p>
<div class="row">
<div class="col-xs-11">
<div class="row">
<label class="control-label">Question 1</label>
<label class="radio">
<input type="radio" >No
</label>
<label class="radio">
<input type="radio" checked="checked">Yes
</label>
</div>
</div>
</div>
<div class="row">
<div class="col-xs-11">
<div class="row">
<label class="control-label">Question 2</label>
<label class="radio">
<input type="radio" >No
</label>
<label class="radio">
<input type="radio" checked="checked">Yes
</label>
</div>
<div class="row show">
<textarea name="123-.Explanation">
My Explaination
</textarea>
</div>
</div>
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
                    'form_sections' => [
                        'Section 1' => [
                            [
                                'question' => 'Question 1',
                                'answers' => [
                                    'No' => false,
                                    'Yes' => true,
                                ],
                              'explanation' => null,
                            ],
                            [
                                'question' => 'Question 2',
                                'answers' => [
                                    'No' => false,
                                    'Yes' => true,
                                ],
                              'explanation' => 'My Explaination',
                            ],
                        ],
                    ],
                ],
            ])
            ->andReturn($crawlResult);
    });

    $this->instance(UpdateCrawlResult::class, $storeCrawlResultMock);

    $job = new ParseDisclosureMaPage($crawlResult);

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

    $job = new ParseDisclosureMaPage($crawlResult);

    $job->handle();
});
