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
use TrueRcm\LaravelWebscrape\Models\CrawlResult;

class ParsePracticeLocationPage implements ShouldQueue
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
            $values = collect([]);
            $this->newLocationNodes()->each(function (Crawler $node, $i) use ($values) {
                $values->push($this->handleNewLocation($node));
            });
            $this->values['new_locations'] = $values->toArray();

            $values = collect([]);
            $this->activeLocationNodes()->each(function (Crawler $node, $i) use ($values) {
                $values->push($this->handleActiveLocation($node));
            });
            $this->values['active_locations'] = $values->toArray();
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

    protected function newLocationNodes(): Crawler
    {
        return $this->crawler->filter('div#healthplanPLGrid > div.e-gridcontent tr');
    }

    protected function activeLocationNodes(): Crawler
    {
        return $this->crawler->filter('div#divActivePracticeLocation > div.divinnerrow');
    }

    protected function handleNewLocation($node): array
    {
        $colNodes = $node->filter('td');

        $nameData = $colNodes->eq(0)->filterXPath('//div');
        $name = $nameData->eq(0)->text();
        $taxId = $nameData->eq(1)->filter('span')->text();
        $addressData = $colNodes->eq(1)->filterXPath('//div[contains(@class, "grid-name-text")]/div')->extract(['_text']);
        $notesData = $colNodes->eq(2)->filterXPath('//p[contains(@id, "pg-tooltiptext")]')->text('');
        $daysElapsedData = $colNodes->eq(3)->filterXPath('//label[contains(@id, "lbl-text-color")]')->text();

        return [
            'name' => $name,
            'tax_id' => $taxId,
            'address' => $addressData,
            'notes' => $notesData,
            'days_elapsed' => $daysElapsedData,
        ];
    }

    protected function handleActiveLocation($node): array
    {
        $nameData = $node->filter('div.divname li');
        $name = $nameData->eq(0)->text();
        $taxId = $nameData->eq(2)->text();
        $addressData = $node->filter('div.divaddress p#practiceLocationAddress')->text('');
        $affiliationData = $node->filter('div.divaffiliation p#practiceAffiliation')->text('');
        $lastConfirmedDate = $node->filter('div.divlastconfirmeddate label')->text('');
        $managedBy = $node->filter('div.divmanagedby span')->text('');

        return [
            'name' => $name,
            'tax_id' => $taxId,
            'address' => $addressData,
            'affiliation_description' => $affiliationData,
            'last_confirmed_date' => $lastConfirmedDate,
            'managed_by' => $managedBy,
        ];
    }
}
