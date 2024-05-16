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


class ParseReferencesPage implements ShouldQueue
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
            $items = $crawler->filter('div#ProfessionalReferencePlaceHolder div.collection-item');

            $items->each(function ($node, $i) use(&$result) {
                $temp = [];

                $temp['provider_type'] = $node->filterXPath('//select[contains(@name, ".ProviderTypeId")]/option[contains(@selected, "selected")]')->text('');
                $temp['first_name'] = $node->filterXPath('//input[contains(@name, ".FirstName")]')->attr('value');
                $temp['last_name'] = $node->filterXPath('//input[contains(@name, ".LastName")]')->attr('value');
                $temp['street_1'] = $node->filterXPath('//input[contains(@name, ".Street1")]')->attr('value');
                $temp['street_2'] = $node->filterXPath('//input[contains(@name, ".Street2")]')->attr('value');
                $temp['city'] = $node->filterXPath('//input[contains(@name, ".City")]')->attr('value');
                $temp['state'] = $node->filterXPath('//select[contains(@name, ".StateId")]/option[contains(@selected, "selected")]')->text('');
                $temp['province'] = $node->filterXPath('//input[contains(@name, ".Province")]')->attr('value');
                $temp['zip_code'] = $node->filterXPath('//input[contains(@name, ".Zipcode")]')->attr('value');
                $temp['country'] = $node->filterXPath('//select[contains(@name, ".CountryId")]/option[contains(@selected, "selected")]')->text('');
                $temp['email_address'] = $node->filterXPath('//input[contains(@name, ".EmailAddress")]')->attr('value');
                $temp['phone_number'] = $node->filterXPath('//input[contains(@name, ".PhoneNumber")]')->attr('value');
                $temp['fax_number'] = $node->filterXPath('//input[contains(@name, ".FaxNumber")]')->attr('value');
                $temp['title'] = $node->filterXPath('//input[contains(@name, ".Title")]')->attr('value');
                $temp['hospital_facility'] = $node->filterXPath('//select[contains(@name, ".HospitalFacilityId")]/option[contains(@selected, "selected")]')->text('');
                $temp['department'] = $node->filterXPath('//input[contains(@name, ".Department")]')->attr('value');

                $result[] = $temp;
            });
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
