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


class ParsePracticeLocationPage implements ShouldQueue
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
            $newLocationsForReview = [];
            $headers = array_filter($crawler->filter('div#healthplanPLGrid > div.e-gridheader div.e-headercelldiv div')
                ->extract(['_text']));

            $contentRows = $crawler->filter('div#healthplanPLGrid > div.e-gridcontent tr');
            $contentRows->each(function ($node, $i) use(&$newLocationsForReview, $headers){
                $colNodes = $node->filter('td');
                $temp = [];

                $nameData = $colNodes->eq(0)->filterXPath('//div');
                $name = $nameData->eq(0)->text();
                $taxId = $nameData->eq(1)->filter('span')->text();
                $temp[$headers[0]] = compact('name', 'taxId');

                $addressData = $colNodes->eq(1)->filterXPath('//div[contains(@class, "grid-name-text")]/div')->extract(['_text']);
                $temp[$headers[1]] = $addressData;

                $notesData = $colNodes->eq(2)->filterXPath('//p[contains(@id, "pg-tooltiptext")]')->text('');
                $temp[$headers[2]] = $notesData;

                $daysElapsedData = $colNodes->eq(3)->filterXPath('//label[contains(@id, "lbl-text-color")]')->text();
                $temp[$headers[3]] = $daysElapsedData;

                $newLocationsForReview[] = $temp;
            });

            $result['newLocationsForReview'] = $newLocationsForReview;

            $activePracticeLocations = [];
            $headers = collect($crawler->filter('div#PracticeLocationGrid > div#divPracticeLocation > div.borderstyle > div')
                ->extract(['_text']))
                ->forget(0)
                ->values()
                ->toArray();

            $contentRows = $crawler->filter('div#divActivePracticeLocation > div.divinnerrow');

            $contentRows->each(function ($node, $i) use(&$activePracticeLocations, $headers){

                $temp = [];

                $nameData = $node->filter('div.divname li');
                $name = $nameData->eq(0)->text();
                $taxId = $nameData->eq(2)->text();
                $temp[$headers[0]] = compact('name', 'taxId');

                $addressData = $node->filter('div.divaddress p#practiceLocationAddress')->text('');
                $temp[$headers[1]] = $addressData;

                $affiliationData = $node->filter('div.divaffiliation p#practiceAffiliation')->text('');
                $temp[$headers[2]] = $affiliationData;

                $lastConfirmedDate = $node->filter('div.divlastconfirmeddate label')->text('');
                $temp[$headers[3]] = $lastConfirmedDate;

                $managedBy = $node->filter('div.divmanagedby span')->text('');
                $temp[$headers[4]] = $managedBy;

                $activePracticeLocations[] = $temp;
            });

            $result['activePracticeLocations'] = $activePracticeLocations;
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
