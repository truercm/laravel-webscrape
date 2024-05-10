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


class ParseProfessionalLiabilityPage implements ShouldQueue
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
            $currentInsurancePolicies   = [];
            $items = $crawler->filterXPath('//div[contains(@id, "SummaryPageGridEditRecord")]');

            $items->each(function ($node, $i) use(&$currentInsurancePolicies){
                $temp = [];
                $id = $node->evaluate('substring-after(@id, "SummaryPageGridEditRecord_")');
                $temp['id'] = $id[0];

                $contentNodes = $node->filter('div.grid-inner');

                $temp['insurance_company__name'] = $contentNodes->eq(0)->text();

                $temp['policy_number'] = Str::of($contentNodes->eq(1)->filter('p')->text())->after(':')->toString();

                $temp['current_effective_date'] = Str::of($contentNodes->eq(1)->text())
                    ->after('Current Effective Date:')
                    ->before('Current Expiration Date:')
                    ->trim()
                    ->toString();

                $temp['current_expiration_date'] = Str::of($contentNodes->eq(1)->filter('text')->text())
                    ->after('Current Expiration Date:')
                    ->trim()
                    ->toString();

                $policyInfoMsg = $contentNodes->eq(1)->filter('div#policyInfoMsg');

                $temp['policy_info_msg'] = $policyInfoMsg->matches('.hide') ? '' : $policyInfoMsg->text();

                $currentInsurancePolicies[] = $temp;
            });

            $result['currentInsurancePolicies'] = $currentInsurancePolicies;

            $result['is_ftca_covered'] = $crawler->filterXPath('//input[@name="IsFTCACovered"]')
                ->filterXPath('//input[@checked="checked"]')
                ->count() ? true : false;

            $result['not_insured'] = $crawler->filterXPath('//input[@name="NotInsured"]')
                ->filterXPath('//input[@checked="checked"]')
                ->count() ? true : false;

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
