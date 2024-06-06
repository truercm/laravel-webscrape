<?php

use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Jobs\ParseSpecialitiesPage;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;

beforeEach(function () {
    Carbon::setTestNow('May 17, 2023 2:13 PM');
});

it('will parse the page', function () {
    $html = <<<HTML
<html>
<body>
<div id="PrimarySpecialityPlaceHolder">
<select name=".SpecialtyNameId">
<option value="123" >Nurse</option>
<option value="456" selected="selected">Physician</option>
</select>
<input name=".PercentOfPractice" value="10">
<label for="BoardCertifid">Board Certified?</label>
<label class="radio">
<input type="radio" >No
</label>
<label class="radio">
<input type="radio" checked="checked">Yes
</label>
<select name=".SpecialtyBoardId">
<option value="234" >Foo</option>
<option value="567" selected="selected">Baa</option>
</select>
<select name=".StateId">
<option value="" >Delhi</option>
<option value="" selected="selected">UP</option>
</select>
<select name=".CountryId">
<option value="" >US</option>
<option value="" selected="selected">India</option>
</select>
<input name=".Street1" value="Line 1">
<input name=".Street2" value="Line 2">
<input name=".City" value="Noida">
<input name=".ZipCode" value="201012">
<input name=".Province" value="USA">
<input name=".CertificationNumber" value="1147666">
<input name=".CertificationDate" value="12/11/2021">
<label for="DoesYourBoardCertificationExpirationDate">Does your board certification have an expiration date?</label>
<label class="radio">
<input type="radio" >No
</label>
<label class="radio">
<input type="radio" checked="checked">Yes
</label>
<input name=".ExpirationDate" value="12/11/2023">
<input name=".RecertificationDate" value="11/12/2024">
<label for="PlanToPursueBoardCertification">I am planning to pursue Board Certification or Re-Certification:</label>
<label class="radio">
<input type="radio" >No
</label>
<label class="radio">
<input type="radio" checked="checked">Yes
</label>
</div>
<div id="SecondarySpecialitySection">
<label for="DoYouHaveASecondarySpecialty">Do you have a Secondary Specialty?</label>
<label class="radio">
<input type="radio" checked="checked">No
</label>
<label class="radio">
<input type="radio" >Yes
</label>
</div>

<label for="CertificationsVM_LifeSupportCertification">Do you have Certifications?</label>
<label class="radio">
<input type="radio" checked="checked">No
</label>
<label class="radio">
<input type="radio" >Yes
</label>
<textarea name="AreasOfProfessionalPracticeInterest">My interest</textarea>

<div id="SpecialExperienceSkillsAndTrainingPlaceHolder">
<div class="row">
<div class="col-xs-6">
<label>Patient populations</label>
<div class="row">
<div class="checkbox-style-dev">
<input type="checkbox" checked="checked">Adolescents
</div>
<div class="checkbox-style-dev">
<input type="checkbox">Children
</div>
</div>
</div>
<div class="col-xs-6">
<label>Physical Conditions</label>
<div class="row">
<div class="checkbox-style-dev">
<input type="checkbox" checked="checked">People with Disabilities
</div>
<div class="checkbox-style-dev">
<input type="checkbox">Physical Disabilities
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
                    'specialties' => [
                        [
                            'is_primary' => true,
                            'nucc_code' => '456',
                            'label' => 'Physician',
                            'percent_of_practice' => '10',
                            'certification_status' => true,
                            'address' => [
                                'address_type' => 'office',
                                'country' => 'India',
                                'state' => 'UP',
                                'line_1' => 'Line 1',
                                'line_2' => 'Line 2',
                                'city' => 'Noida',
                                'province' => 'USA',
                                'zip' => '201012',
                                'zip_extension' => null,
                            ],
                            'certification_board' => 'Baa',
                            'certification_number' => '1147666',
                            'certification_created_at' => '12/11/2021',
                            'certification_expires' => true,
                            'certification_expires_at' => '12/11/2023',
                            'certification_updated_at' => '11/12/2024',
                            'plans_to_update' => true,
                        ],
                    ],
                    'Do you have Certifications?' => [
                        'No' => true,
                        'Yes' => false,
                    ],
                    'Other Interests' => 'My interest',
                    'skills' => [
                        'patient_populations' => [
                            'Adolescents' => true,
                            'Children' => false,
                        ],
                        'physical_conditions' => [
                            'People with Disabilities' => true,
                            'Physical Disabilities' => false,
                        ],
                    ],
                ],
            ])
            ->andReturn($crawlResult);
    });

    $this->instance(UpdateCrawlResult::class, $storeCrawlResultMock);

    $job = new ParseSpecialitiesPage($crawlResult);

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

    $job = new ParseSpecialitiesPage($crawlResult);

    $job->handle();
});
