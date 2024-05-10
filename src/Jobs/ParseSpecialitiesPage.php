<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\DomCrawler\Crawler;
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
            'process_status' => 'completed',
        ]);
        $result = [];
        $crawler = new Crawler($this->crawlResult->body, $this->crawlResult->url);

        try{
            $primarySpecialty = [];
            $primarySpecialtySection = $crawler->filter('div#PrimarySpecialityPlaceHolder');
            $primarySpecialty['index'] =  $primarySpecialtySection->filter('input[name="SpecialityDetailsVM.index"]')->attr('value');

            $input =  $primarySpecialtySection->filterXPath('//select[contains(@name, ".SpecialtyNameId")]/option[contains(@selected, "selected")]');
            $primarySpecialty['specialty'] = [
                'key' => $input->count() ? $input->attr('value') : null,
                'value' => $input->count() ? $input->text() : null
            ];

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
            $primarySpecialty['Name of Certifying Board'] = [
                'key' => $input->count() ? $input->attr('value') : null,
                'value' => $input->count() ? $input->text() : null
            ];

            $input =  $primarySpecialtySection->filterXPath('//select[contains(@name, ".CountryId")]/option[contains(@selected, "selected")]');
            $primarySpecialty['Country'] = [
                'key' => $input->count() ? $input->attr('value') : null,
                'value' => $input->count() ? $input->text() : null
            ];

            $input =  $primarySpecialtySection->filterXPath('//select[contains(@name, ".StateId")]/option[contains(@selected, "selected")]');
            $primarySpecialty['State'] = [
                'key' => $input->count() ? $input->attr('value') : null,
                'value' => $input->count() ? $input->text() : null
            ];

            $primarySpecialty['Street 1'] =  $primarySpecialtySection->filterXPath('//input[contains(@name, ".Street1")]')->attr('value');
            $primarySpecialty['Street 2'] =  $primarySpecialtySection->filterXPath('//input[contains(@name, ".Street2")]')->attr('value');
            $primarySpecialty['City'] =  $primarySpecialtySection->filterXPath('//input[contains(@name, ".City")]')->attr('value');
            $primarySpecialty['Province'] =  $primarySpecialtySection->filterXPath('//input[contains(@name, ".Province")]')->attr('value');
            $primarySpecialty['Zip Code'] =  $primarySpecialtySection->filterXPath('//input[contains(@name, ".ZipCode")]')->attr('value');
            $primarySpecialty['Certification Number'] =  $primarySpecialtySection->filterXPath('//input[contains(@name, ".CertificationNumber")]')->attr('value');
            $primarySpecialty['Initial Certification Date'] =  $primarySpecialtySection->filterXPath('//input[contains(@name, ".CertificationDate")]')->attr('value');


            $boardCertificationHaveExpirationDate = [];
            $inputs = $primarySpecialtySection->filterXPath('//label[contains(@for, "DoesYourBoardCertificationExpirationDate")]')
                ->siblings()
                ->filter('label.radio');

            $inputs->each(function ($node, $i) use(&$boardCertificationHaveExpirationDate){
                $radio = $node->filterXPath('//input[@type="radio"]')
                    ->filterXPath('//input[@checked="checked"]');
                $boardCertificationHaveExpirationDate[$node->text()] = $radio->count() ? true : false;

            });

            $primarySpecialty['Does your board certification have an expiration date?'] = $boardCertificationHaveExpirationDate;

            $primarySpecialty['Expiration Date'] =  $primarySpecialtySection->filterXPath('//input[contains(@name, ".ExpirationDate")]')->attr('value');
            $primarySpecialty['Last Recertification Date'] =  $primarySpecialtySection->filterXPath('//input[contains(@name, ".RecertificationDate")]')->attr('value');


            $planningToCertificationOrReCertification = [];
            $inputs = $primarySpecialtySection->filterXPath('//label[contains(@for, "PlanToPursueBoardCertification")]')
                ->siblings()
                ->filter('label.radio');

            $inputs->each(function ($node, $i) use(&$planningToCertificationOrReCertification){
                $radio = $node->filterXPath('//input[@type="radio"]')
                    ->filterXPath('//input[@checked="checked"]');
                $planningToCertificationOrReCertification[$node->text()] = $radio->count() ? true : false;

            });

            $primarySpecialty['I am planning to pursue Board Certification or Re-Certification'] = $planningToCertificationOrReCertification;

            $result['primarySpecialty'] = $primarySpecialty;

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

            $specialExperienceSkills = [];
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
            $specialExperienceSkills['patientPopulations'] = $patientPopulations;

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
            $specialExperienceSkills['physicalConditions'] = $physicalConditions;

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
            $specialExperienceSkills['areaOfExpertise'] = $areaOfExpertise;


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
            $specialExperienceSkills['treatmentOptions'] = $treatmentOptions;

            $result['specialExperienceSkills'] = $specialExperienceSkills;
        }catch(\Exception $e){
            $error = __("Error :message at line :line", ['message' => $e->getMessage(), 'line' => $e->getLine()]);
            $result['error'] = $error;
            $this->crawlResult->forceFill([
                'process_status' => 'error',
            ]);
        }

        $this->crawlResult->forceFill([
            'processed_at' => now(),
            'result' => $result
        ])->save();

    }
}
