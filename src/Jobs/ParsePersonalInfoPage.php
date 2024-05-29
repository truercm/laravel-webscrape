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

class ParsePersonalInfoPage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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
        $this->crawlResult->forceFill([
            'process_status' => CrawlResultStatus::COMPLETED,
        ]);
        $result = [];
        $crawler = new Crawler($this->crawlResult->body, $this->crawlResult->url);

        try {
            $input = $crawler->filterXPath('//select[@id="NuccGroupId"]/option[contains(@selected, "selected")]');
            $result['NUCC Grouping'] = [
                'value' => $input->count() ? $input->attr('value') : null,
                'text' => $input->count() ? $input->text() : null,
            ];

            $input = $crawler->filterXPath('//select[@id="ProviderTypeId"]/option[contains(@selected, "selected")]');
            $result['Provider Type'] = [
                'value' => $input->count() ? $input->attr('value') : null,
                'text' => $input->count() ? $input->text() : null,
            ];

            $input = $crawler->filterXPath('//select[@id="PracticeSetting"]/option[contains(@selected, "selected")]');
            $result['Practice Setting'] = [
                'value' => $input->attr('value'),
                'text' => $input->text(),
            ];

            $input = $crawler->filterXPath('//select[contains(@id, "PracticeStateDetails")]/option[contains(@selected, "selected")]');
            $result['Primary Practice State'] = [
                'value' => $input->count() ? $input->attr('value') : null,
                'text' => $input->count() ? $input->text() : null,
            ];

            $result['Additional Practice States'] = [];

            $nameSections = $crawler->filterXPath('//div[@id="NamesSection"]');

            $nameSections->each(function ($node, $i) use (&$result) {
                if ('Name' == $node->text()) {
                    $nameNode = $node->nextAll()
                        ->filter('p[data-name="name-grid-header"]');
                    $result['name'] = $nameNode->text('');
                }
            });

            $result['aliases'] = [];

            $result['addresses'] = [
            ];

            $emails = [];
            $nameSections->each(function ($node, $i) use (&$emails) {
                if ('Primary Email Address' == $node->text()) {
                    $primaryEmailNode = $node->nextAll()
                        ->filter('p[data-name="name-grid-header"]');
                    if ($primaryEmailNode->count()) {
                        $emails[] = [
                            'address' => $primaryEmailNode->text(''),
                            'allows_notifications' => false,
                            'is_primary' => true,
                        ];
                    }
                }
            });

            $additionalEmails = $crawler->filterXPath('//div[@class="additional-email-main"]')
                ->filter('input[type="text"]')
                ->extract(['value']);

            collect($additionalEmails)->each(function ($email) use (&$emails) {
                $emails[] = [
                    'address' => $email,
                    'allows_notifications' => false,
                    'is_primary' => false,
                ];
            });
            $result['emails'] = $emails;

            $result['Additional Emails'] = $additionalEmails;

            $result['ssns'] = [['number' => $crawler->filter('input[name="SSN"]')->attr('value')]];

            $result['npis'] = [['number' => $crawler->filter('input[name="NPINumber"]')->attr('value')]];

            $input = $crawler->filterXPath('//select[@id="GenderCode"]/option[contains(@selected, "selected")]');
            $result['gender'] = $input->count() ? $input->text() : null;

            $isTransgende = $crawler->filterXPath('//input[@name="IIdentifyAsTransgender"]')
                ->filterXPath('//input[@checked="checked"]')
                ->count() ? true : false;

            if ($isTransgende) {
                $result['gender'] = 'Not Known';
            }

            $result['birth_date'] = $crawler->filter('input[name="BirthDate"]')->attr('value');

            $input = $crawler->filterXPath('//select[@id="CitizenshipCountryId"]/option[contains(@selected, "selected")]');
            $result['citizenship_id'] = $input->count() ? $input->text() : null;

            $result['birth_city'] = $crawler->filter('input[name="BirthCity"]')->attr('value');

            $input = $crawler->filterXPath('//select[@id="BirthStateId"]/option[contains(@selected, "selected")]');
            $result['birth_state'] = $input->count() ? $input->text() : null;

            $input = $crawler->filterXPath('//select[@id="BirthCountryId"]/option[contains(@selected, "selected")]');
            $result['birth_country_id'] = $input->count() ? $input->text() : null;

            $result['languages'] = $crawler->filterXPath('//select[@id="LanguageSpoken_List"]/option[contains(@selected, "selected")]')->extract(['_text']);

            $raceEthnicity = [];
            $crawler->filterXPath('//input[contains(@name, "RaceAndEthnicity")]')
                ->filterXPath('//input[@type="checkbox"]')
                ->each(function ($node, $i) use (&$raceEthnicity) {
                    $labelNode = $node->closest('div.checker')
                        ->nextAll()
                        ->eq(1);

                    $text = $labelNode->text();

                    $possilleTooltipNode = $labelNode->nextAll();

                    if ($possilleTooltipNode->matches('.tooltiplocal')) {
                        $text .= ' ' . $possilleTooltipNode->text();
                    }

                    $raceEthnicity[$text] = $node->attr('checked') ? true : false;
                });

            $result['demographic'] = $raceEthnicity;
        } catch (\Exception $e) {
            $error = __('Error :message at line :line', ['message' => $e->getMessage(), 'line' => $e->getLine()]);
            $result['error'] = $error;
            $this->crawlResult->forceFill([
                'process_status' => CrawlResultStatus::ERROR,
            ]);
        }

        $this->crawlResult->forceFill([
            'processed_at' => now(),
            'result' => $result,
        ])->save();
    }
}
