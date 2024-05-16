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

class ParseDocumentsPage implements ShouldQueue
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

        $documents = [];

        try{
            $contentRows = $crawler->filter('div.e-gridcontent tr');

            $contentRows->each(function ($node, $i) use(&$documents, $contentRows){

                if($i < $contentRows->count()-1){
                    $colNodes = $node->filter('td');
                    $temp = [];

                    $temp['name'] = $colNodes->eq(0)->filter('a')->text();
                    $temp['link'] = $colNodes->eq(0)->filter('a')->link()->getUri();
                    $temp['state'] = $colNodes->eq(1)->filter('span')->text('');
                    $temp['uploaded_date'] = $colNodes->eq(2)->filter('span')->text('');
                    $temp['expiration_date'] = $colNodes->eq(3)->filter('span')->text('');
                    $temp['status'] = $colNodes->eq(4)->filter('span')->text('');

                    $documents[] = $temp;
                }
            });
        }catch(\Exception $e){
            $error = __("Error :message at line :line", ['message' => $e->getMessage(), 'line' => $e->getLine()]);
            $result['error'] = $error;
            $this->crawlResult->forceFill([
                'process_status' => CrawlResultStatus::ERROR,
            ]);
        }

        $result['documents'] = $documents;

        $this->crawlResult->forceFill([
            'processed_at' => now(),
            'result' => $result
        ])->save();

    }
}
