<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Exceptions\CrawlException;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;

class ParseProfessionalLiabilityPage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected array $values = [];
    protected CrawlResultStatus $process_status = CrawlResultStatus::COMPLETED;
    protected Crawler $crawler;
    protected ?string $error = null;

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
        $this->crawler = new Crawler($this->crawlResult->body, $this->crawlResult->url);

        try {
            $policies = collect([]);

            throw_if(
                0 == $this->nodes()->count() /* is not good */,
                CrawlException::parsingFailed($this->crawlResult)
            );

            $this->nodes()->each(function (Crawler $node, $i) use ($policies) {
                $policies->push($this->handleNode($node));
            });

            $this->values['current_insurance_policies'] = $policies->toArray();

            $this->values['is_ftca_covered'] = $this->crawler->filterXPath('//input[@name="IsFTCACovered"]')
                ->filterXPath('//input[@checked="checked"]')
                ->count() ? true : false;

            $this->values['is_insured'] = $this->crawler->filterXPath('//input[@name="NotInsured"]')
                ->filterXPath('//input[@checked="checked"]')
                ->count() ? false : true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->process_status = CrawlResultStatus::ERROR;
        }

        if ($this->error) {
            $this->values['error'] = $this->error;
        }

        resolve(UpdateCrawlResult::class)->run($this->crawlResult, $this->toArray());
    }

    protected function toArray(): array
    {
        return [
            'processed_at' => now(),
            'result' => $this->values,
            'process_status' => $this->process_status->value,
        ];
    }

    protected function nodes(): Crawler
    {
        return $this->crawler->filterXPath('//div[contains(@id, "SummaryPageGridEditRecord")]');
    }

    protected function handleNode($node): array
    {
        $id = $node->evaluate('substring-after(@id, "SummaryPageGridEditRecord_")');

        $contentNodes = $node->filter('div.grid-inner');

        $policyInfoMsg = $contentNodes->eq(1)->filter('div#policyInfoMsg');

        return [
            'id' => head($id),
            'insurance_company__name' => $contentNodes->eq(0)->text(),
            'policy_number' => Str::of($contentNodes->eq(1)->filter('p')->text())->after(':')->__toString(),
            'current_effective_date' => Str::of($contentNodes->eq(1)->text())
                ->after('Current Effective Date:')
                ->before('Current Expiration Date:')
                ->trim()
                ->__toString(),
            'current_expiration_date' => Str::of($contentNodes->eq(1)->filter('text')->text())
                ->after('Current Expiration Date:')
                ->trim()
                ->__toString(),
            'policy_info_msg' => $policyInfoMsg->matches('.hide') ? '' : $policyInfoMsg->text(),
        ];
    }
}
