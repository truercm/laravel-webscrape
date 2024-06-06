<?php

use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Jobs\ParseEducationAndProfessionalTrainingPage;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;

beforeEach(function () {
    Carbon::setTestNow('May 17, 2023 2:13 PM');
});

it('will parse the page', function () {
    $html = <<<HTML
<html>
<body>
<div class="edu-main">
<div class="row">
<div id="SummaryPageGridEditRecord">
<div class="grid-inner">
    <p>Master of Science (MS)</p>
</div>
<div class="grid-inner">
<div>
    <p>Northeastern University</p>
</div>
<p>
</p><p> August 2015  to August 2017 </p>

<p></p>

<p class="lato-bold-smalltext-black">
    Boston ,  MA
</p>

</div>
<div class="grid-inner col-md-3 text-right">

</div>
</div>
</div>
</div>
<div class="profTraining-main">

</div>

<input id="ProviderProfessionalDetails_isculturalcompetencytrainingcompleted" type="radio" value="100000000" checked="checked">
<input id="ProviderProfessionalDetails_isculturalcompetencytrainingcompleted" type="radio" value="100000001">
</body>
</html>
HTML;

    $crawlResult = CrawlResult::factory()->create(['body' => $html]);

    $storeCrawlResultMock = $this->mock(UpdateCrawlResult::class, function (MockInterface $mock) use ($crawlResult) {

        $institute = <<<HTML
Northeastern University\nAugust 2015 to August 2017\nBoston , MA
HTML;
        $mock->shouldReceive('run')
            ->once()
            ->with($crawlResult, [
                'processed_at' => now(),
                'process_status' => CrawlResultStatus::COMPLETED->value,
                'result' => [
                    "educations" => [
                        [
                            "degree" => "Master of Science (MS)",
                            "institution" => $institute
                        ],
                    ],
                    "trainings" => [],
                    "Completed cultural competency training" => true,
                ],
            ])
            ->andReturn($crawlResult);
    });

    $this->instance(UpdateCrawlResult::class, $storeCrawlResultMock);

    $job = new ParseEducationAndProfessionalTrainingPage($crawlResult);

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
            ->andReturn($crawlResult);
    });

    $this->instance(UpdateCrawlResult::class, $storeCrawlResultMock);

    $job = new ParseEducationAndProfessionalTrainingPage($crawlResult);

    $job->handle();
});
