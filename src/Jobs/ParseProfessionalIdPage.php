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


class ParseProfessionalIdPage implements ShouldQueue
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
            $professionalLicenses = [];

            $headers = array_filter($crawler->filter('div#ProfessionLicenseDetails > div.e-gridheader div.e-headercelldiv')
                ->extract(['_text']));
            $headers = [
                'state',
                'current',
                'number',
                'expires_at'
            ];
            $contentRows = $crawler->filter('div#ProfessionLicenseDetails > div.e-gridcontent tr');
            $contentRows->each(function ($node, $i) use(&$professionalLicenses, $headers){
                $colNodes = $node->filter('td');
                $temp = [];
                foreach($headers as $key=>$header){
                    $temp[$header] = $colNodes->eq($key)->text();
                }
                $professionalLicenses[] = $temp;
            });

            $result['licenses'] = $professionalLicenses;

            $cdsRegistrations = [];

            $headers = array_filter($crawler->filter('div#CDSDetails > div.e-gridheader div.e-headercelldiv')
                ->extract(['_text']));
            $headers = [
                'state',
                'number',
                'issued_at',
                'expires_at'
            ];
            $contentRows = $crawler->filter('div#CDSDetails > div.e-gridcontent tr');
            $contentRows->each(function ($node, $i) use(&$cdsRegistrations, $headers){
                $colNodes = $node->filter('td');
                $temp = [];
                foreach($headers as $key=>$header){
                    $temp[$header] = $colNodes->eq($key)->text();
                }
                $cdsRegistrations[] = $temp;
            });

            $result['cdc'] = $cdsRegistrations;


            $medicaids = [];
            $medicaidItems = $crawler->filter('div#MedicaidPlaceHolder > div.collection-item');
            $medicaidItems->each(function ($node, $i) use(&$medicaids){
                $temp = [];
                $temp['number'] =  $node->filterXPath('//input[contains(@name, ".Number")]')->attr('value');
                $temp['state'] =  $node->filterXPath('//select[contains(@name, ".StateId")]/option[contains(@selected, "selected")]')->text();
                $medicaids[] = $temp;
            });

            $result['medicaids'] = $medicaids;

            $medicares = [];

            $medicareItems = $crawler->filter('div#MedicarePlaceHolder > div.collection-item');
            $medicareItems->each(function ($node, $i) use(&$medicares){
                $temp = [];
                $temp['number'] =  $node->filterXPath('//input[contains(@name, ".Number")]')->attr('value');
                $temp['state'] =  $node->filterXPath('//select[contains(@name, ".StateId")]/option[contains(@selected, "selected")]')->text();
                $medicares[] = $temp;
            });

            $result['medicares'] = $medicares;
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
