<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\DomCrawler\Crawler;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;


class ParseHospitalAffiliationPage implements ShouldQueue
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
            $result['admittingPrivileges'] = [];

            $admittingArrangements  = [];
            $items = $crawler->filter('div#edit-admitting-arrangements');

            $items->each(function ($node, $i) use(&$admittingArrangements){
                $temp = [];
                $container = $node->filterXPath('//div[contains(@id, "SummaryPageGridEditRecord")]');

                $id = $container->evaluate('substring-after(@id, "SummaryPageGridEditRecord_")');
                $temp['id'] = $id[0];

                $contentNodes = $container->filter('div.grid-inner');

                $temp['hospital_name'] = $contentNodes->eq(0)->text();

                $temp['status'] = $contentNodes->eq(1)->filter('p')->eq(0)->text();
                $temp['location'] = $contentNodes->eq(1)->filter('p')->eq(1)->text();

                $admittingArrangements[] = $temp;
            });

            $result['admittingArrangements'] = $admittingArrangements;
            $result['nonAdmittingAffiliations'] = [];
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
