<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\DomCrawler\Crawler;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;

class ParsePersonalInfoPage implements ShouldQueue
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
            $this->handlePersonalInformation()
                ->handleName()
                ->handleAddress()
                ->handleContactInformation()
                ->handlePersonalIdentificationNumbers()
                ->handleDemographics();
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

    protected function handlePersonalInformation(): self
    {
        $nuccGroupId = $this->crawler->filterXPath('//select[@id="NuccGroupId"]/option[contains(@selected, "selected")]');
        $providerTypeId = $this->crawler->filterXPath('//select[@id="ProviderTypeId"]/option[contains(@selected, "selected")]');
        $practiceSetting = $this->crawler->filterXPath('//select[@id="PracticeSetting"]/option[contains(@selected, "selected")]');
        $practiceStateDetails = $this->crawler->filterXPath('//select[contains(@id, "PracticeStateDetails")]/option[contains(@selected, "selected")]');
        $additionalPracticeStates = $this->crawler->filterXPath('//select[@id="StateIdListModel_List"]/option[contains(@selected, "selected")]');

        $this->values['nucc_grouping'] = [
            'value' => $nuccGroupId->count() ? $nuccGroupId->attr('value') : null,
            'text' => $nuccGroupId->count() ? $nuccGroupId->text() : null,
        ];

        $this->values['provider_type'] = [
            'value' => $providerTypeId->count() ? $providerTypeId->attr('value') : null,
            'text' => $providerTypeId->count() ? $providerTypeId->text() : null,
        ];

        $this->values['practice_setting'] = [
             'value' => $practiceSetting->count() ? $practiceSetting->attr('value') : null,
             'text' => $practiceSetting->count() ? $practiceSetting->text() : null,
        ];

        $this->values['primary_practice_state'] = [
             'value' => $practiceStateDetails->count() ? $practiceStateDetails->attr('value') : null,
             'text' => $practiceStateDetails->count() ? $practiceStateDetails->text() : null,
        ];

        $this->values['additional_practice_states'] = $additionalPracticeStates->extract(['_text']);

        return $this;
    }

    protected function handleAddress(): self
    {
        $this->values['addresses'] = [];

        return $this;
    }

    protected function handleName(): self
    {
        $nameSections = $this->crawler->filterXPath('//div[@id="NamesSection"]');

        $nameSections->each(function ($node, $i) use (&$result) {
            if ('Name' == $node->text()) {
                $nameNode = $node->nextAll()
                    ->filter('p[data-name="name-grid-header"]');
                $this->values['name'] = $nameNode->text('');
            }
        });

        $this->values['aliases'] = [];

        return $this;
    }

    protected function handleContactInformation(): self
    {
        $nameSections = $this->crawler->filterXPath('//div[@id="NamesSection"]');

        $emails = collect([]);
        $nameSections->each(function ($node, $i) use ($emails) {
            if ('Primary Email Address' == $node->text()) {
                $primaryEmailNode = $node->nextAll()
                    ->filter('p[data-name="name-grid-header"]');
                if ($primaryEmailNode->count()) {
                    $emails->push([
                        'address' => $primaryEmailNode->text(''),
                        'allows_notifications' => false,
                        'is_primary' => true,
                    ]);
                }
            }
        });

        $additionalEmails = $this->crawler->filterXPath('//div[@class="additional-email-main"]')
            ->filter('input[type="text"]')
            ->extract(['value']);

        collect($additionalEmails)->each(function ($email) use ($emails) {
            $emails->push([
                'address' => $email,
                'allows_notifications' => false,
                'is_primary' => false,
            ]);
        });

        $this->values['emails'] = $emails->toArray();

        return $this;
    }

    protected function handlePersonalIdentificationNumbers(): self
    {
        $this->values['ssns'] = [['number' => $this->crawler->filter('input[name="SSN"]')->attr('value')]];

        $this->values['npis'] = [['number' => $this->crawler->filter('input[name="NPINumber"]')->attr('value')]];

        return $this;
    }

    protected function handleDemographics(): self
    {
        $input = $this->crawler->filterXPath('//select[@id="GenderCode"]/option[contains(@selected, "selected")]');
        $this->values['gender'] = $input->count() ? $input->text() : null;

        $isTransgende = $this->crawler->filterXPath('//input[@name="IIdentifyAsTransgender"]')
            ->filterXPath('//input[@checked="checked"]')
            ->count() ? true : false;

        if ($isTransgende) {
            $this->values['gender'] = 'Not Known';
        }

        $this->values['birth_date'] = $this->crawler->filter('input[name="BirthDate"]')->attr('value');

        $input = $this->crawler->filterXPath('//select[@id="CitizenshipCountryId"]/option[contains(@selected, "selected")]');
        $this->values['citizenship_id'] = $input->count() ? $input->text() : null;

        $this->values['birth_city'] = $this->crawler->filter('input[name="BirthCity"]')->attr('value');

        $input = $this->crawler->filterXPath('//select[@id="BirthStateId"]/option[contains(@selected, "selected")]');
        $this->values['birth_state'] = $input->count() ? $input->text() : null;

        $input = $this->crawler->filterXPath('//select[@id="BirthCountryId"]/option[contains(@selected, "selected")]');
        $this->values['birth_country_id'] = $input->count() ? $input->text() : null;

        $raceEthnicity = collect([]);
        $this->crawler->filterXPath('//input[contains(@name, "RaceAndEthnicity")]')
            ->filterXPath('//input[@type="checkbox"]')
            ->each(function ($node, $i) use ($raceEthnicity) {
                $labelNode = $node->closest('div.checker')
                    ->nextAll()
                    ->eq(1);

                $text = $labelNode->text();

                $possilleTooltipNode = $labelNode->nextAll();

                if ($possilleTooltipNode->matches('.tooltiplocal')) {
                    $text .= ' ' . $possilleTooltipNode->text();
                }

                $raceEthnicity->put($text, $node->attr('checked') ? true : false);
            });

        $this->values['race_ethnicity'] = $raceEthnicity->toArray();

        $this->values['languages'] = $this->crawler->filterXPath('//select[@id="LanguageSpoken_List"]/option[contains(@selected, "selected")]')->extract(['_text']);

        return $this;
    }
}
