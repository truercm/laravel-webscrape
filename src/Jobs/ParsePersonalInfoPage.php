<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\DomCrawler\Crawler;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;


class ParsePersonalInfoPage implements ShouldQueue
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
            $input = $crawler->filterXPath('//select[@id="NuccGroupId"]/option[contains(@selected, "selected")]');
            $result['NUCC Grouping'] = [
                'value' => $input->count() ? $input->attr('value') : null,
                'text' => $input->count() ? $input->text() : null
            ];

            $input = $crawler->filterXPath('//select[@id="ProviderTypeId"]/option[contains(@selected, "selected")]');
            $result['Provider Type'] = [
                'value' => $input->count() ? $input->attr('value') : null,
                'text' => $input->count() ? $input->text() : null
            ];

            $input = $crawler->filterXPath('//select[@id="PracticeSetting"]/option[contains(@selected, "selected")]');
            $result['Practice Setting'] = [
                'value' => $input->attr('value'),
                'text' => $input->text()
            ];

            $input = $crawler->filterXPath('//select[contains(@id, "PracticeStateDetails")]/option[contains(@selected, "selected")]');
            $result['Primary Practice State'] = [
                'value' => $input->count() ? $input->attr('value') : null,
                'text' => $input->count() ? $input->text() : null
            ];

            $result['Additional Practice States'] = [];

            $nameSections = $crawler->filterXPath('//div[@id="NamesSection"]');

            $nameSections->each(function ($node, $i) use(&$result){
                if($node->text() == 'Name'){
                    $nameNode = $node->nextAll()
                        ->filter('p[data-name="name-grid-header"]');
                    $result['Name'] = $nameNode->text('');
                }
            });

            $result['Other Names'] = [];

            $result['Address'] = [
                'Home' => null,
                'Mailing' => null,
            ];

            $nameSections->each(function ($node, $i) use(&$result){
                if($node->text() == 'Primary Email Address'){
                    $primaryEmailNode = $node->nextAll()
                        ->filter('p[data-name="name-grid-header"]');
                    $result['Primary Email Address'] = $primaryEmailNode->text('');
                }
            });

            $additionalEmails = $crawler->filterXPath('//div[@class="additional-email-main"]')
                ->filter('input[type="text"]')
                ->extract(['value']);

            $result['Additional Emails'] = $additionalEmails;

            $result['Social Security Number'] =  $crawler->filter('input[name="SSN"]')->attr('value');

            $result['NPI Number'] =  $crawler->filter('input[name="NPINumber"]')->attr('value');

            $input = $crawler->filterXPath('//select[@id="GenderCode"]/option[contains(@selected, "selected")]');
            $result['Gender'] = [
                'value' => $input->count() ? $input->attr('value') : null,
                'text' => $input->count() ? $input->text() : null
            ];

            $result['Birth Date'] =  $crawler->filter('input[name="BirthDate"]')->attr('value');

            $input = $crawler->filterXPath('//select[@id="CitizenshipCountryId"]/option[contains(@selected, "selected")]');
            $result['Citizenship Country'] = [
                'value' => $input->count() ? $input->attr('value') : null,
                'text' => $input->count() ? $input->text() : null
            ];

            $result['Birth City'] =  $crawler->filter('input[name="BirthCity"]')->attr('value');

            $input = $crawler->filterXPath('//select[@id="BirthStateId"]/option[contains(@selected, "selected")]');
            $result['Birth State'] = [
                'value' => $input->count() ? $input->attr('value') : null,
                'text' => $input->count() ? $input->text() : null
            ];

            $input = $crawler->filterXPath('//select[@id="BirthCountryId"]/option[contains(@selected, "selected")]');
            $result['Birth Country'] = [
                'value' => $input->count() ? $input->attr('value') : null,
                'text' => $input->count() ? $input->text() : null
            ];

            $result['Languages'] = null;

            $raceEthnicity = [];
            $crawler->filterXPath('//input[contains(@name, "RaceAndEthnicity")]')
                ->filterXPath('//input[@type="checkbox"]')
                ->filterXPath('//input[@checked="checked"]')
                ->each(function ($node, $i) use(&$raceEthnicity){
                    $labelNode = $node->closest('div.checker')
                        ->nextAll()
                        ->eq(1);

                    $text = $labelNode->text();

                    $possilleTooltipNode = $labelNode->nextAll();

                    if($possilleTooltipNode->matches('.tooltiplocal')){
                        $text .= ' '.$possilleTooltipNode->text();
                    }

                    $raceEthnicity[] = $text;
                });

            $result['Race/Ethnicity'] = $raceEthnicity;
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
