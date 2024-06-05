<?php

use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Jobs\ParseReferencesPage;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;

beforeEach(function () {
    Carbon::setTestNow('May 17, 2023 2:13 PM');
});

it('will parse the page', function () {
    $html = <<<HTML
<html>
<body>
<div id="ProfessionalReferencePlaceHolder">
<div class="collection-item">
<select name=".ProviderTypeId">
<option value="" >Foo</option>
<option value="" selected="selected">Baa</option>
</select>
<input name=".FirstName" value="Arun">
<input name=".MiddleName" value="Kr">
<input name=".LastName" value="Singh">
<input name=".Street1" value="Line 1">
<input name=".Street2" value="Line 2">
<input name=".City" value="Noida">
<input name=".Zipcode" value="201012">
<input name=".Province" value="USA">
<input name=".PhoneNumber" value="234-1256">
<input name=".FaxNumber" value="768-2346">
<input name=".EmailAddress" value="test@test.com">
<input name=".Title" value="Avinash">
<input name=".Department" value="Maha">
<select name=".HospitalFacilityId">
<option value="" >AIMS</option>
<option value="" selected="selected">FORTIS</option>
</select>
<select name=".StateId">
<option value="" >Delhi</option>
<option value="" selected="selected">UP</option>
</select>
<select name=".CountryId">
<option value="" >US</option>
<option value="" selected="selected">India</option>
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
                    "references" => [
                        [
                            "provider_type" => "Baa",
                            "first_name" => "Arun",
                            "last_name" => "Singh",
                            "street_1" => "Line 1",
                            "street_2" => "Line 2",
                            "city" => "Noida",
                            "state" => "UP",
                            "province" => "USA",
                            "zip_code" => "201012",
                            "country" => "India",
                            "email_address" => "test@test.com",
                            "phone_number" => "234-1256",
                            "fax_number" => "768-2346",
                            "title" => "Avinash",
                            "hospital_facility" => "FORTIS",
                            "department" => "Maha",
                        ],
                    ],
                ],
            ])
            ->andReturn($crawlResult);
    });

    $this->instance(UpdateCrawlResult::class, $storeCrawlResultMock);

    $job = new ParseReferencesPage($crawlResult);

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

    $job = new ParseReferencesPage($crawlResult);

    $job->handle();
});
