<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\DomCrawler\Crawler;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;


class ParseSpecialitiesPage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected CrawlResult $crawlResult
    )
    {
    }

    /*
     * "target" is CAQH
     *
     * "subject" is a single crowler run for a providler
     * */

    public function handle()
    {
        $this->crawlResult->forceFill([
            'process_status' => CrawlResultStatus::COMPLETED,
        ]);
        $result = [];
        $crawler = new Crawler($this->crawlResult->body, $this->crawlResult->url);

        try{
            $specialties = [];
            $primarySpecialty = [];
            $primarySpecialtySection = $crawler->filter('div#PrimarySpecialityPlaceHolder');

            $primarySpecialty['is_primary'] = true;
            $input =  $primarySpecialtySection->filterXPath('//select[contains(@name, ".SpecialtyNameId")]/option[contains(@selected, "selected")]');
            $primarySpecialty['nucc_code'] = $input->count() ? $input->attr('value') : null;
            $primarySpecialty['label'] = $input->count() ? $input->text() : null;
            $primarySpecialty['certification_status'] = true;

            $address = [];
            $address['address_type'] = "office";
            $input =  $primarySpecialtySection->filterXPath('//select[contains(@name, ".CountryId")]/option[contains(@selected, "selected")]');
            $address['country'] = $input->count() ? $input->text() : null;

            $input =  $primarySpecialtySection->filterXPath('//select[contains(@name, ".StateId")]/option[contains(@selected, "selected")]');
            $address['state'] = $input->count() ? $input->text() : null;

            $address['line_1'] =  $primarySpecialtySection->filterXPath('//input[contains(@name, ".Street1")]')->attr('value');
            $address['line_2'] =  $primarySpecialtySection->filterXPath('//input[contains(@name, ".Street2")]')->attr('value');
            $address['city'] =  $primarySpecialtySection->filterXPath('//input[contains(@name, ".City")]')->attr('value');
            $address['province'] =  $primarySpecialtySection->filterXPath('//input[contains(@name, ".Province")]')->attr('value');
            $address['zip'] =  $primarySpecialtySection->filterXPath('//input[contains(@name, ".ZipCode")]')->attr('value');
            $address['zip_extension'] =  null;

            $primarySpecialty['address'] = $address;

            $primarySpecialty['Percent of Practice'] = $crawler->filterXPath('//input[contains(@name, ".PercentOfPractice")]')->attr('value');
            $inputs = $primarySpecialtySection->filter('div#PrimaryBoardCertifiedDiv label.radio');
            $boardCertified = [];
            $inputs->each(function ($node, $i) use(&$boardCertified){
                $radio = $node->filterXPath('//input[@type="radio"]')
                    ->filterXPath('//input[@checked="checked"]');
                $boardCertified[$node->text()] = $radio->count() ? true : false;

            });

            $primarySpecialty['boardCertified'] = $boardCertified;

            $input =  $primarySpecialtySection->filterXPath('//select[contains(@name, ".SpecialtyBoardId")]/option[contains(@selected, "selected")]');
            $primarySpecialty['certification_board'] = $input->count() ? $input->text() : null;


            $primarySpecialty['certification_number'] =  $primarySpecialtySection->filterXPath('//input[contains(@name, ".CertificationNumber")]')->attr('value');
            $primarySpecialty['certification_created_at'] =  $primarySpecialtySection->filterXPath('//input[contains(@name, ".CertificationDate")]')->attr('value');


            $boardCertificationHaveExpirationDate = [];
            $inputs = $primarySpecialtySection->filterXPath('//label[contains(@for, "DoesYourBoardCertificationExpirationDate")]')
                ->siblings()
                ->filter('label.radio');

            $inputs->each(function ($node, $i) use(&$boardCertificationHaveExpirationDate){
                $radio = $node->filterXPath('//input[@type="radio"]')
                    ->filterXPath('//input[@checked="checked"]');
                $boardCertificationHaveExpirationDate[$node->text()] = $radio->count() ? true : false;

            });

            $primarySpecialty['certification_expires'] = $boardCertificationHaveExpirationDate['Yes'];

            $primarySpecialty['certification_expires_at'] =  $primarySpecialtySection->filterXPath('//input[contains(@name, ".ExpirationDate")]')->attr('value');
            $primarySpecialty['certification_updated_at'] =  $primarySpecialtySection->filterXPath('//input[contains(@name, ".RecertificationDate")]')->attr('value');


            $planningToCertificationOrReCertification = [];
            $inputs = $primarySpecialtySection->filterXPath('//label[contains(@for, "PlanToPursueBoardCertification")]')
                ->siblings()
                ->filter('label.radio');

            $inputs->each(function ($node, $i) use(&$planningToCertificationOrReCertification){
                $radio = $node->filterXPath('//input[@type="radio"]')
                    ->filterXPath('//input[@checked="checked"]');
                $planningToCertificationOrReCertification[$node->text()] = $radio->count() ? true : false;

            });

            $primarySpecialty['plans_to_update'] = $planningToCertificationOrReCertification['Yes'];

            $specialties[] = $primarySpecialty;

            $result['specialties'] = $specialties;

            $secondarySpecialty = [];
            $secondarySpecialtySection = $crawler->filter('div#SecondarySpecialitySection');

            $haveSecondarySpecialty = [];
            $inputs = $secondarySpecialtySection->filterXPath('//label[contains(@for, "DoYouHaveASecondarySpecialty")]')
                ->siblings()
                ->filter('label.radio');

            $inputs->each(function ($node, $i) use(&$haveSecondarySpecialty){
                $radio = $node->filterXPath('//input[@type="radio"]')
                    ->filterXPath('//input[@checked="checked"]');
                $haveSecondarySpecialty[$node->text()] = $radio->count() ? true : false;

            });

            $secondarySpecialty['Do you have a Secondary Specialty?'] = $haveSecondarySpecialty;

            $result['secondarySpecialty'] = $secondarySpecialty;

            $haveCertifications = [];
            $inputs = $crawler->filterXPath('//label[contains(@for, "CertificationsVM_LifeSupportCertification")]')
                ->siblings()
                ->filter('label.radio');

            $inputs->each(function ($node, $i) use(&$haveCertifications){
                $radio = $node->filterXPath('//input[@type="radio"]')
                    ->filterXPath('//input[@checked="checked"]');
                $haveCertifications[$node->text()] = $radio->count() ? true : false;

            });

            $result['Do you have Certifications?'] = $haveCertifications;

            $result['Other Interests'] = $crawler->filter('textarea[name="AreasOfProfessionalPracticeInterest"]')->text();

            $specialExperienceSkillsSection = $crawler->filter('div#SpecialExperienceSkillsAndTrainingPlaceHolder');


            $patientPopulations = [];
            $inputs = $specialExperienceSkillsSection->filterXPath('//label[contains(@for, "Patient_populations")]')
                ->siblings()
                ->filter('div.row')
                ->filter('div.checkbox-style-dev');

            $inputs->each(function ($node, $i) use(&$patientPopulations){
                $checkbox = $node->filterXPath('//input[@type="checkbox"]')
                    ->filterXPath('//input[@checked="checked"]');
                $patientPopulations[$node->text()] = $checkbox->count() ? true : false;

            });
            $result['patient_populations'] = $patientPopulations;

            $physicalConditions = [];
            $inputs = $specialExperienceSkillsSection->filterXPath('//label[contains(@for, "Physical_Conditions")]')
                ->siblings()
                ->filter('div.row')
                ->filter('div.checkbox-style-dev');

            $inputs->each(function ($node, $i) use(&$physicalConditions){
                $checkbox = $node->filterXPath('//input[@type="checkbox"]')
                    ->filterXPath('//input[@checked="checked"]');
                $physicalConditions[$node->text()] = $checkbox->count() ? true : false;

            });
            $result['physical_conditions'] = $physicalConditions;

            $areaOfExpertise = [];
            $inputs = $specialExperienceSkillsSection->filterXPath('//label[contains(@for, "Area_of_Expertise")]')
                ->siblings()
                ->filter('div.row')
                ->filter('div.checkbox-style-dev');

            $inputs->each(function ($node, $i) use(&$areaOfExpertise){
                $checkbox = $node->filterXPath('//input[@type="checkbox"]')
                    ->filterXPath('//input[@checked="checked"]');
                $areaOfExpertise[$node->text()] = $checkbox->count() ? true : false;

            });
            $result['areas_of_expertise'] = $areaOfExpertise;


            $treatmentOptions = [];
            $inputs = $specialExperienceSkillsSection->filterXPath('//label[contains(@for, "Treatment_Options")]')
                ->siblings()
                ->filter('div.row')
                ->filter('div.checkbox-style-dev');

            $inputs->each(function ($node, $i) use(&$treatmentOptions){
                $checkbox = $node->filterXPath('//input[@type="checkbox"]')
                    ->filterXPath('//input[@checked="checked"]');
                $treatmentOptions[$node->text()] = $checkbox->count() ? true : false;

            });
            $result['treatment_options'] = $treatmentOptions;

        }catch(\Exception $e){
            $error = __("Error :message at line :line", ['message' => $e->getMessage(), 'line' => $e->getLine()]);
            $result['error'] = $error;
            $this->crawlResult->forceFill([
                'process_status' => CrawlResultStatus::ERROR,
            ]);
        }

        $this->crawlResult->forceFill([
            'processed_at' => now(),
            'result' => $result
        ])->save();

    }
}
