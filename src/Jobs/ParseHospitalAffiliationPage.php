<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\DomCrawler\Crawler;
use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Exceptions\CrawlException;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;

class ParseHospitalAffiliationPage implements ShouldQueue
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
        $this->crawler =  new Crawler($crawlResult->body, $crawlResult->url);
    }


    /*
     * "target" is CAQH
     *
     * "subject" is a single crowler run for a providler
     * */

    public function handle()
    {
        try {
            $admittingArrangements = collect([]);

            throw_if(
                0 == $this->nodes()->count() /* is not good */,
                CrawlException::parsingFailed($this->crawlResult)
            );

            $this->nodes()->each(function (Crawler $node, $i) use($admittingArrangements){
                $admittingArrangements->push($this->handleNode($node));
            });

            $this->values['admitting_privileges'] = $admittingArrangements->toArray();
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->process_status = CrawlResultStatus::ERROR;
        }

        if($this->error){
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
        return $this->crawler->filter('div#edit-admitting-arrangements');
    }

    protected function handleNode($node): array
    {
        $container = $node->filterXPath('//div[contains(@id, "SummaryPageGridEditRecord")]');

        $id = $container->evaluate('substring-after(@id, "SummaryPageGridEditRecord_")');

        $contentNodes = $container->filter('div.grid-inner');

        return [
            'id' => head($id),
            'name' => $contentNodes->eq(0)->text(''),
            'status' => $contentNodes->eq(1)->filter('p')->eq(0)->text(''),
            'location' => $contentNodes->eq(1)->filter('p')->eq(1)->text(''),
        ];
    }
}
