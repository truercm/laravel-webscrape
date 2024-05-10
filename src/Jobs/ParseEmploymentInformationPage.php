<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;


class ParseEmploymentInformationPage implements ShouldQueue
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
            $employmentRecords = [];
            $items = $crawler->filterXPath('//div[contains(@id, "Employment-Edit-Records")]/div[contains(@id, "SummaryPageGridEditRecord")]');

            $items->each(function ($node, $i) use(&$employmentRecords){
                $temp = [];
                $id = $node->evaluate('substring-after(@id, "SummaryPageGridEditRecord_")');
                $temp['id'] = $id[0];

                $contentNodes = $node->filter('div.grid-inner');

                $temp['company__name'] = $contentNodes->eq(0)->text();

                $temp['from'] = Str::of($contentNodes->eq(1)->filter('p')->text())
                    ->before('-')
                    ->trim()
                    ->toString();

                $after = Str::of($contentNodes->eq(1)->filter('p')->text())
                    ->after('-')
                    ->trim();

                $temp['to'] = $after->is('Current Employment') ? null : $after->toString();

                $temp['currently_epmloyed'] = $after->is('Current Employment') ? true : false;

                $employmentRecords[] = $temp;
            });

            $result['employment_records'] = $employmentRecords;

            $servedOrServingInMilitary = [];
            $inputs = $crawler->filterXPath('//label[contains(@for, "HaveYouEverServedInTheUSMilitary")]')
                ->siblings()
                ->filter('label.radio');

            $inputs->each(function ($node, $i) use(&$servedOrServingInMilitary){
                $radio = $node->filterXPath('//input[@type="radio"]')
                    ->filterXPath('//input[@checked="checked"]');
                $servedOrServingInMilitary[$node->text()] = $radio->count() ? true : false;
            });

            $result['Have you ever served or are you currently serving in the United States Military?'] =  $servedOrServingInMilitary;


            $currentlyOnActiveMilitaryDuty = [];
            $inputs = $crawler->filterXPath('//label[contains(@for, "AreYouCurrentlyOnActivemilitaryDuty")]')
                ->siblings()
                ->filter('label.radio');

            $inputs->each(function ($node, $i) use(&$currentlyOnActiveMilitaryDuty){
                $radio = $node->filterXPath('//input[@type="radio"]')
                    ->filterXPath('//input[@checked="checked"]');
                $currentlyOnActiveMilitaryDuty[$node->text()] = $radio->count() ? true : false;
            });

            $result['Are you currently on active military duty?'] =  $currentlyOnActiveMilitaryDuty;


            $reservesorNationalGuard = [];
            $inputs = $crawler->filterXPath('//label[contains(@for, "ReservesorNationalGuard")]')
                ->siblings()
                ->filter('label.radio');

            $inputs->each(function ($node, $i) use(&$reservesorNationalGuard){
                $radio = $node->filterXPath('//input[@type="radio"]')
                    ->filterXPath('//input[@checked="checked"]');
                $reservesorNationalGuard[$node->text()] = $radio->count() ? true : false;
            });

            $result['Are you currently in the Reserves or National Guard?'] =  $reservesorNationalGuard;
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
