<?php

use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Jobs\ParseCredentialingContactPage;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow('May 17, 2023 2:13 PM');
});

it('will parse the page', function () {
    $html = <<<HTML
<html>
<body>
<div id="CredentialingContactPlaceHolder">
<div class="collection-item">
<input name=".CredentialingContactVM.index" value="1234">
<input name=".Id" value="56">
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
<input name=".ContactTitle" value="Avinash">
<input name=".ContactHoursofAvailability" value="after 2 pm">
<select name=".StateId">
<option value="" >Delhi</option>
<option value="" selected="selected">UP</option>
</select>
<select name=".CountryId">
<option value="" >US</option>
<option value="" selected="selected">India</option>
</select>
<select name=".LocationType">
<option value="" >Office</option>
<option value="" selected="selected">Residence</option>
</select>
<select name=".PracticeLocations.List">
<option value="" >Location 1</option>
<option value="" selected="selected">Location 2</option>
<option value="" selected="selected">Location 3</option>
</select>
<label for="PrimaryCredentialingContact">Primary Credentialing Contact</label>
<label class="radio">
<input type="radio" >No
</label>
<label class="radio">
<input type="radio" checked="checked">Yes
</label>
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
                    "credentialing_contacts" => [
                        [
                            "is_primary" => true,
                            "index" => "1234",
                            "id" => "56",
                            "first_name" => "Arun",
                            "middle_name" => "Kr",
                            "last_name" => "Singh",
                            "line_1" => "Line 1",
                            "line_2" => "Line 2",
                            "city" => "Noida",
                            "state" => "UP",
                            "zip" => "201012",
                            "country" => "India",
                            "province" => "USA",
                            "phone_number" => "234-1256",
                            "fax_number" => "768-2346",
                            "email_address" => "test@test.com",
                            "location_type" => "Residence",
                            "location" => [
                                "Location 2",
                                "Location 3",
                            ],
                            "contact_title" => "Avinash",
                            "contact_hours_of_availability" => "after 2 pm",
                        ],
                    ]
                ],
            ])
            ->andReturn($crawlResult);
    });

    $this->instance(UpdateCrawlResult::class, $storeCrawlResultMock);

    $job = new ParseCredentialingContactPage($crawlResult);

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

    $job = new ParseCredentialingContactPage($crawlResult);

    $job->handle();
});
