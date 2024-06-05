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

class ParseReferencesPage implements ShouldQueue
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
            $references = collect([]);

            throw_if(
                0 == $this->nodes()->count() /* is not good */,
                CrawlException::parsingFailed($this->crawlResult)
            );
            $this->nodes()->each(function (Crawler $node, $i) use ($references) {
                $references->push($this->handleNode($node));
            });

            $this->values['references'] = $references->toArray();
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
        return $this->crawler->filter('div#ProfessionalReferencePlaceHolder div.collection-item');
    }

    protected function handleNode($node): array
    {
        return [
            'provider_type' => $node->filterXPath('//select[contains(@name, ".ProviderTypeId")]/option[contains(@selected, "selected")]')->text(''),
            'first_name' => $node->filterXPath('//input[contains(@name, ".FirstName")]')->attr('value'),
            'last_name' => $node->filterXPath('//input[contains(@name, ".LastName")]')->attr('value'),
            'street_1' => $node->filterXPath('//input[contains(@name, ".Street1")]')->attr('value'),
            'street_2' => $node->filterXPath('//input[contains(@name, ".Street2")]')->attr('value'),
            'city' => $node->filterXPath('//input[contains(@name, ".City")]')->attr('value'),
            'state' => $node->filterXPath('//select[contains(@name, ".StateId")]/option[contains(@selected, "selected")]')->text(''),
            'province' => $node->filterXPath('//input[contains(@name, ".Province")]')->attr('value'),
            'zip_code' => $node->filterXPath('//input[contains(@name, ".Zipcode")]')->attr('value'),
            'country' => $node->filterXPath('//select[contains(@name, ".CountryId")]/option[contains(@selected, "selected")]')->text(''),
            'email_address' => $node->filterXPath('//input[contains(@name, ".EmailAddress")]')->attr('value'),
            'phone_number' => $node->filterXPath('//input[contains(@name, ".PhoneNumber")]')->attr('value'),
            'fax_number' => $node->filterXPath('//input[contains(@name, ".FaxNumber")]')->attr('value'),
            'title' => $node->filterXPath('//input[contains(@name, ".Title")]')->attr('value'),
            'hospital_facility' => $node->filterXPath('//select[contains(@name, ".HospitalFacilityId")]/option[contains(@selected, "selected")]')->text(''),
            'department' => $node->filterXPath('//input[contains(@name, ".Department")]')->attr('value'),
        ];
    }
}
