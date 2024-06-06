<?php

use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Jobs\ParsePersonalInfoPage;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;

beforeEach(function () {
    Carbon::setTestNow('May 17, 2023 2:13 PM');
});

it('will parse the page', function () {
    $html = <<<HTML
<html>
<body>
<select id="NuccGroupId">
<option value="123" >Physician</option>
<option value="345" selected="selected">Nurse</option>
</select>
<select id="ProviderTypeId">
<option value="123" >Physician</option>
<option value="345" selected="selected">Nurse</option>
</select>
<select id="PracticeSetting">
<option value="123" >Administrative</option>
<option value="345" selected="selected">Military</option>
</select>
<select id="PracticeStateDetails">
<option value="123" >MA</option>
<option value="345" selected="selected">DA</option>
</select>
<select id="StateIdListModel_List" multi-select="true">
<option value="123" selected="selected" >NY</option>
<option value="345" selected="selected">OP</option>
</select>
<div id="NamesSection">Name</div>
<div>
<p class="grid-header" data-name="name-grid-header">Robyn  Kitchell </p>
</div>

<div id="NamesSection">Primary Email Address</div>
<div>
<p class="grid-header" data-name="name-grid-header">rubyn@test.com</p>
</div>
<div class="additional-email-main">
<input type="text" value="second-email@test.com">
<input type="text" value="third-email@test.com">
</div>

<input name="SSN" value="87631">
<input name="NPINumber" value="675312">

<select id="GenderCode" >
<option value="123">Male</option>
<option value="345" selected="selected">Female</option>
</select>
<input type="checkbox" checked="checked" name="IIdentifyAsTransgender">
<input name="BirthDate" value="12/11/1990">
<select id="BirthStateId">
<option value="" >Delhi</option>
<option value="" selected="selected">UP</option>
</select>
<select id="CitizenshipCountryId">
<option value="" >US</option>
<option value="" selected="selected">India</option>
</select>
<select id="BirthCountryId">
<option value="" selected="selected">US</option>
<option value="">India</option>
</select>
<input name="BirthCity" value="Noida">
<div>
<div class="checker">
<input type="checkbox" checked="checked" name="RaceAndEthnicity">
</div>
<input type="hidden">
<label>American Indian or Alaska Native</label>
<span></span>
</div>
<div>
<div class="checker">
<input type="checkbox" name="RaceAndEthnicity">
</div>
<input type="hidden">
<label>Black or African American</label>
<span class="tooltiplocal">(Black, African American, African...) </span>
</div>
<select id="LanguageSpoken_List" multi-select="true">
<option value="123" selected="selected" >English</option>
<option value="345" selected="selected">French</option>
</select>

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
                    "nucc_grouping" => [
                        "value" => "345",
                        "text" => "Nurse",
                    ],
                    "provider_type" => [
                        "value" => "345",
                        "text" => "Nurse",
                    ],
                    "practice_setting" => [
                        "value" => "345",
                        "text" => "Military",
                    ],
                    "primary_practice_state" => [
                        "value" => "345",
                        "text" => "DA",
                    ],
                    "additional_practice_states" => [
                        "NY",
                        "OP",
                    ],
                    "name" => "Robyn Kitchell",
                    "aliases" => [],
                    "addresses" => [],
                    "emails" => [
                        [
                            "address" => "rubyn@test.com",
                            "allows_notifications" => false,
                            "is_primary" => true,
                        ],
                        [
                            "address" => "second-email@test.com",
                            "allows_notifications" => false,
                            "is_primary" => false,
                        ],
                        [
                            "address" => "third-email@test.com",
                            "allows_notifications" => false,
                            "is_primary" => false,
                        ],
                    ],
                    "ssns" => [
                        [
                            "number" => "87631",
                        ],
                    ],
                    "npis" => [
                        [
                            "number" => "675312",
                        ],
                    ],
                    "gender" => "Not Known",
                    "birth_date" => "12/11/1990",
                    "citizenship_id" => "India",
                    "birth_city" => "Noida",
                    "birth_state" => "UP",
                    "birth_country_id" => "US",
                    "race_ethnicity" => [
                        "American Indian or Alaska Native" => true,
                        "Black or African American (Black, African American, African...)" => false,
                    ],
                    "languages" => [
                        "English",
                        "French",
                    ],
                ],
            ])
            ->andReturn($crawlResult);
    });

    $this->instance(UpdateCrawlResult::class, $storeCrawlResultMock);

    $job = new ParsePersonalInfoPage($crawlResult);

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

    $job = new ParsePersonalInfoPage($crawlResult);

    $job->handle();
});
