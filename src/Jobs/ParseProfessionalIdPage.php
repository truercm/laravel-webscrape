<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\DomCrawler\Crawler;
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
            'process_status' => 'completed',
        ]);
        $result = [];
        $crawler = new Crawler($this->crawlResult->body, $this->crawlResult->url);

        $professionalLicenses = [];

        $headers = array_filter($crawler->filter('div#ProfessionLicenseDetails > div.e-gridheader div.e-headercelldiv')
            ->extract(['_text']))
        ;
        $contentRows = $crawler->filter('div#ProfessionLicenseDetails > div.e-gridcontent tr');
        $contentRows->each(function ($node, $i) use(&$professionalLicenses, $headers){
            $colNodes = $node->filter('td');
            $temp = [];
            foreach($headers as $key=>$header){
                $temp[$header] = $colNodes->eq($key)->text();
            }
            $professionalLicenses[] = $temp;
        });

        $result['professionalLicenses'] = $professionalLicenses;

        $cdsRegistrations = [];

        $headers = array_filter($crawler->filter('div#CDSDetails > div.e-gridheader div.e-headercelldiv')
            ->extract(['_text']))
        ;
        $contentRows = $crawler->filter('div#CDSDetails > div.e-gridcontent tr');
        $contentRows->each(function ($node, $i) use(&$cdsRegistrations, $headers){
            $colNodes = $node->filter('td');
            $temp = [];
            foreach($headers as $key=>$header){
                $temp[$header] = $colNodes->eq($key)->text();
            }
            $cdsRegistrations[] = $temp;
        });

        $result['cdsRegistrations'] = $cdsRegistrations;


        $medicaids = [];
        $medicaidItems = $crawler->filter('div#MedicaidPlaceHolder > div.collection-item');
        $medicaidItems->each(function ($node, $i) use(&$medicaids){
            $temp = [];
            $temp['index'] =  $node->filter('input[name="MedicaidVM.index"]')->attr('value');
            $temp['Number'] =  $node->filterXPath('//input[contains(@name, ".Number")]')->attr('value');
            $temp['StateId'] =  $node->filterXPath('//select[contains(@name, ".StateId")]/option[contains(@selected, "selected")]')->text();
            $medicaids[] = $temp;
        });

        $result['medicaids'] = $medicaids;

        $medicares = [];
        try{

            $medicareItems = $crawler->filter('div#MedicarePlaceHolder > div.collection-item');
            $medicareItems->each(function ($node, $i) use(&$medicares){
                $temp = [];
                $temp['index'] =  $node->filter('input[name="MedicareVM.index"]')->attr('value');
                $temp['Number'] =  $node->filterXPath('//input[contains(@name, ".Number")]')->attr('value');
                $temp['StateId'] =  $node->filterXPath('//select[contains(@name, ".StateId")]/option[contains(@selected, "selected")]')->text();
                $medicares[] = $temp;
            });
        }catch(\Exception $e){
            $error = __("Error :message at line :line", ['message' => $e->getMessage(), 'line' => $e->getLine()]);
            $medicares['error'] = $error;
            $this->crawlResult->forceFill([
                'process_status' => 'error',
            ]);
        }

        $result['medicares'] = $medicares;

        $this->crawlResult->forceFill([
            'processed_at' => now(),
            'result' => $result
        ])->save();

    }
}
