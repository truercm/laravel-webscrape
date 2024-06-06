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
use TrueRcm\LaravelWebscrape\Models\CrawlResult;

class ParseEmploymentInformationPage implements ShouldQueue
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
            $this->employmentNodes()->each(function (Crawler $node, $i) use ($values) {
                $values->push($this->handleEmployment($node));
            });
            $this->values['employment_records'] = $values->toArray();

            $this->handleMilitaryInfo();
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

    protected function employmentNodes(): Crawler
    {
        return $this->crawler->filterXPath('//div[contains(@id, "Employment-Edit-Records")]/div[contains(@id, "SummaryPageGridEditRecord")]');
    }

    protected function handleEmployment($node): array
    {
        $id = $node->evaluate('substring-after(@id, "SummaryPageGridEditRecord_")');

        $contentNodes = $node->filter('div.grid-inner');

        $after = Str::of($contentNodes->eq(1)->filter('p')->text())
            ->after('-')
            ->trim();

        return [
            'id' => head($id),
            'company_name' => $contentNodes->eq(0)->text(),
            'from' => Str::of($contentNodes->eq(1)->filter('p')->text())
                ->before('-')
                ->trim()
                ->__toString(),
            'to' => $after->is('Current Employment') ? null : $after->__toString(),
            'currently_epmloyed' => $after->is('Current Employment') ? true : false,
        ];
    }

    protected function handleMilitaryInfo()
    {
        $this->values['Have you ever served or are you currently serving in the United States Military?'] = (bool) $this->crawler
            ->filterXPath('//input[contains(@name, ".HaveYouEverServedInTheUSMilitary")]')
            ->filterXPath('//input[@type="radio"]')
            ->reduce(function ($node, $i) {
                return 'checked' == $node->attr('checked') and '100000000' == $node->attr('value');
            })
            ->count();

        $this->values['Are you currently on active military duty??'] = (bool) $this->crawler
            ->filterXPath('//input[contains(@name, ".AreYouCurrentlyOnActivemilitaryDuty")]')
            ->filterXPath('//input[@type="radio"]')
            ->reduce(function ($node, $i) {
                return 'checked' == $node->attr('checked') and '100000000' == $node->attr('value');
            })
            ->count();

        $this->values['Are you currently in the Reserves or National Guard?'] = (bool) $this->crawler
            ->filterXPath('//input[contains(@name, ".ReservesorNationalGuard")]')
            ->filterXPath('//input[@type="radio"]')
            ->reduce(function ($node, $i) {
                return 'checked' == $node->attr('checked') and '100000000' == $node->attr('value');
            })
            ->count();
    }
}
