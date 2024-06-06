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

class ParseProfessionalIdPage implements ShouldQueue
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
            $this->licenseNodes()->each(function (Crawler $node, $i) use ($values) {
                $values->push($this->handleLicense($node));
            });
            $this->values['licenses'] = $values->toArray();

            $values = collect([]);
            $this->cdsNodes()->each(function (Crawler $node, $i) use ($values) {
                $values->push($this->handleCds($node));
            });
            $this->values['cds'] = $values->toArray();

            $values = collect([]);
            $this->medicaIdNodes()->each(function (Crawler $node, $i) use ($values) {
                $values->push($this->handleMedicalId($node));
            });
            $this->values['medicaids'] = $values->toArray();

            $values = collect([]);
            $this->medicareNodes()->each(function (Crawler $node, $i) use ($values) {
                $values->push($this->handleMedicalId($node));
            });
            $this->values['medicares'] = $values->toArray();
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

    protected function licenseNodes(): Crawler
    {
        return $this->crawler->filter('div#ProfessionLicenseDetails > div.e-gridcontent tr');
    }

    protected function cdsNodes(): Crawler
    {
        return $this->crawler->filter('div#CDSDetails > div.e-gridcontent tr');
    }

    protected function deaNodes(): Crawler
    {
        return $this->crawler->filter('div#DEARegistrationSection  > div.e-gridcontent tr');
    }

    protected function medicaIdNodes(): Crawler
    {
        return $this->crawler->filter('div#MedicaidPlaceHolder > div.collection-item');
    }

    protected function medicareNodes(): Crawler
    {
        return $this->crawler->filter('div#MedicarePlaceHolder > div.collection-item');
    }

    protected function handleLicense($node): array
    {
        $colNodes = $node->filter('td')
            ->reduce(function ($node, $i) {
                return $i > 0;
            });

        return [
             'state' => $colNodes->eq(0)->text(),
             'current' => $colNodes->eq(1)->text(),
             'number' => $colNodes->eq(2)->text(),
             'expires_at' => $colNodes->eq(3)->text(),
        ];
    }

    protected function handleCds($node): array
    {
        $colNodes = $node->filter('td')
            ->reduce(function ($node, $i) {
                return $i > 0;
            });

        return [
            'state' => $colNodes->eq(0)->text(),
            'number' => $colNodes->eq(1)->text(),
            'issued_at' => $colNodes->eq(2)->text(),
            'expires_at' => $colNodes->eq(3)->text(),
        ];
    }

    protected function handleMedicalId($node): array
    {
        return [
            'number' => $node->filterXPath('//input[contains(@name, ".Number")]')->attr('value'),
            'state' => $node->filterXPath('//select[contains(@name, ".StateId")]/option[contains(@selected, "selected")]')->text(),
        ];
    }
}
