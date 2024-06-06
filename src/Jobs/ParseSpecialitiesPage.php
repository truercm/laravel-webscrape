<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Exceptions\CrawlException;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;

class ParseSpecialitiesPage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected array $values = [];
    protected CrawlResultStatus $process_status = CrawlResultStatus::COMPLETED;
    protected Crawler $crawler;
    protected ?string $error = null;

    public function __construct(
        protected CrawlResult $crawlResult
    ) {
    }

    /*
     * "target" is CAQH
     *
     * "subject" is a single crowler run for a providler
     * */

    public function handle()
    {
        $this->crawler = new Crawler($this->crawlResult->body, $this->crawlResult->url);

        try {
            $specialty = collect([]);

            throw_if(
                0 == $this->primarySection()->count() /* is not good */,
                CrawlException::parsingFailed($this->crawlResult)
            );

            $specialty->push($this->handleSpecialty($this->primarySection()));
            $specialty->push($this->handleSecondarySpecialty($this->secondarySection()));

            $this->values['specialties'] = $specialty->filter()->toArray();

            $haveCertifications = [];
            $inputs = $this->crawler->filterXPath('//label[contains(@for, "CertificationsVM_LifeSupportCertification")]')
                ->siblings()
                ->filter('label.radio');

            $inputs->each(function ($node, $i) use (&$haveCertifications) {
                $radio = $node->filterXPath('//input[@type="radio"]')
                    ->filterXPath('//input[@checked="checked"]');
                $haveCertifications[$node->text()] = $radio->count() ? true : false;
            });

            $this->values['Do you have Certifications?'] = $haveCertifications;

            $this->values['Other Interests'] = $this->crawler->filter('textarea[name="AreasOfProfessionalPracticeInterest"]')->text();

            $skills = collect([]);

            $this->skillsNodes()->each(function (Crawler $node, $i) use ($skills) {
                $this->handleSkillsNode($node, $skills);
            });
            $this->values['skills'] = $skills->toArray();
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->process_status = CrawlResultStatus::ERROR;
        }

        if ($this->error) {
            $this->values['error'] = $this->error;
        }

        resolve(UpdateCrawlResult::class)->run($this->crawlResult, $this->toArray());
    }

    protected function toArray(): array
    {
        return [
            'processed_at' => now(),
            'result' => $this->values,
            'process_status' => $this->process_status->value,
        ];
    }

    protected function primarySection(): Crawler
    {
        return $this->crawler->filter('div#PrimarySpecialityPlaceHolder');
    }

    protected function secondarySection(): Crawler
    {
        return $this->crawler->filter('div#SecondarySpecialitySection');
    }

    protected function skillsNodes(): Crawler
    {
        return $this->crawler->filter('div#SpecialExperienceSkillsAndTrainingPlaceHolder div.col-xs-6');
    }

    protected function handleSpecialty($specialtySection): array
    {
        $specialty = [];

        $specialty['is_primary'] = 'PrimarySpecialityPlaceHolder' == $specialtySection->attr('id');
        $input = $specialtySection->filterXPath('//select[contains(@name, ".SpecialtyNameId")]/option[contains(@selected, "selected")]');
        $specialty['nucc_code'] = $input->count() ? $input->attr('value') : null;
        $specialty['label'] = $input->count() ? $input->text() : null;
        $specialty['percent_of_practice'] = $specialtySection->filterXPath('//input[contains(@name, ".PercentOfPractice")]')->attr('value');

        $inputs = $specialtySection->filterXPath('//label[contains(@for, "BoardCertifid")]')
            ->siblings()
            ->filter('label.radio');
        $boardCertified = [];
        $inputs->each(function ($node, $i) use (&$boardCertified) {
            $radio = $node->filterXPath('//input[@type="radio"]')
                ->filterXPath('//input[@checked="checked"]');
            $boardCertified[$node->text()] = $radio->count() ? true : false;
        });

        $specialty['certification_status'] = $boardCertified['Yes'];

        if ($boardCertified['Yes']) {
            $address = [];
            $address['address_type'] = 'office';
            $input = $specialtySection->filterXPath('//select[contains(@name, ".CountryId")]/option[contains(@selected, "selected")]');
            $address['country'] = $input->count() ? $input->text() : null;

            $input = $specialtySection->filterXPath('//select[contains(@name, ".StateId")]/option[contains(@selected, "selected")]');
            $address['state'] = $input->count() ? $input->text() : null;

            $address['line_1'] = $specialtySection->filterXPath('//input[contains(@name, ".Street1")]')->attr('value');
            $address['line_2'] = $specialtySection->filterXPath('//input[contains(@name, ".Street2")]')->attr('value');
            $address['city'] = $specialtySection->filterXPath('//input[contains(@name, ".City")]')->attr('value');
            $address['province'] = $specialtySection->filterXPath('//input[contains(@name, ".Province")]')->attr('value');
            $address['zip'] = $specialtySection->filterXPath('//input[contains(@name, ".ZipCode")]')->attr('value');
            $address['zip_extension'] = null;

            $specialty['address'] = $address;

            $input = $specialtySection->filterXPath('//select[contains(@name, ".SpecialtyBoardId")]/option[contains(@selected, "selected")]');
            $specialty['certification_board'] = $input->count() ? $input->text() : null;

            $specialty['certification_number'] = $specialtySection->filterXPath('//input[contains(@name, ".CertificationNumber")]')->attr('value');
            $specialty['certification_created_at'] = $specialtySection->filterXPath('//input[contains(@name, ".CertificationDate")]')->attr('value');

            $boardCertificationHaveExpirationDate = [];
            $inputs = $specialtySection->filterXPath('//label[contains(@for, "DoesYourBoardCertificationExpirationDate")]')
                ->siblings()
                ->filter('label.radio');

            $inputs->each(function ($node, $i) use (&$boardCertificationHaveExpirationDate) {
                $radio = $node->filterXPath('//input[@type="radio"]')
                    ->filterXPath('//input[@checked="checked"]');
                $boardCertificationHaveExpirationDate[$node->text()] = $radio->count() ? true : false;
            });

            $specialty['certification_expires'] = $boardCertificationHaveExpirationDate['Yes'];

            $specialty['certification_expires_at'] = $specialtySection->filterXPath('//input[contains(@name, ".ExpirationDate")]')->attr('value');
            $specialty['certification_updated_at'] = $specialtySection->filterXPath('//input[contains(@name, ".RecertificationDate")]')->attr('value');

            $planningToCertificationOrReCertification = [];

            $planToPursueBoardCertification = $specialtySection->filterXPath('//label[contains(@for, "PlanToPursueBoardCertification")]');
            if ($planToPursueBoardCertification->count()) {
                $inputs = $specialtySection->filterXPath('//label[contains(@for, "PlanToPursueBoardCertification")]')
                    ->siblings()
                    ->filter('label.radio');

                $inputs->each(function ($node, $i) use (&$planningToCertificationOrReCertification) {
                    $radio = $node->filterXPath('//input[@type="radio"]')
                        ->filterXPath('//input[@checked="checked"]');
                    $planningToCertificationOrReCertification[$node->text()] = $radio->count() ? true : false;
                });

                $specialty['plans_to_update'] = $planningToCertificationOrReCertification['Yes'];
            }
        }

        return $specialty;
    }

    protected function handleSecondarySpecialty($secondarySpecialtySection): ?array
    {
        $specialty = null;
        $haveSecondarySpecialty = [];
        $inputs = $secondarySpecialtySection->filterXPath('//label[contains(@for, "DoYouHaveASecondarySpecialty")]')
            ->siblings()
            ->filter('label.radio');

        $inputs->each(function ($node, $i) use (&$haveSecondarySpecialty) {
            $radio = $node->filterXPath('//input[@type="radio"]')
                ->filterXPath('//input[@checked="checked"]');
            $haveSecondarySpecialty[$node->text()] = $radio->count() ? true : false;
        });

        if ($haveSecondarySpecialty['Yes']) {
            $specialty = $this->handleSpecialty($secondarySpecialtySection);
        }

        return $specialty;
    }

    protected function handleSkillsNode($skillsNode, $skills): Collection
    {
        $values = [];
        $label = $skillsNode->filter('label')->first();
        $text = Str::of($label->text())->snake()->__toString();

        $inputs = $label
            ->siblings()
            ->filter('div.row')
            ->filter('div.checkbox-style-dev');

        $inputs->each(function ($node, $i) use (&$values) {
            $checkbox = $node->filterXPath('//input[@type="checkbox"]')
                ->filterXPath('//input[@checked="checked"]');
            $values[$node->text()] = $checkbox->count() ? true : false;
        });

        return $skills->put($text, $values);
    }
}
