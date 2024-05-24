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

class ParseCredentialingContactPage implements ShouldQueue
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
            $credentialingContacts = collect([]);

            throw_if(
                0 == $this->nodes()->count() /* is not good */,
                CrawlException::parsingFailed($this->crawlResult)
            );
            $this->nodes()->each(function (Crawler $node, $i) use($credentialingContacts){
                $credentialingContacts->push($this->handleNode($node));
            });

            $this->values['credentialing_contacts'] = $credentialingContacts->toArray();
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
        return $this->crawler->filter('div#CredentialingContactPlaceHolder div.collection-item');
    }

    protected function handleNode($node): array
    {
        $primaryCredentialingContactLabel = $node->filterXPath('//label[contains(@for, "PrimaryCredentialingContact")]');

        if ($primaryCredentialingContactLabel->count()) {
            $primaryCredentialingContactLabel->siblings()
                ->filter('label.radio')
                ->each(function ($node, $i) use (&$primaryCredentialingContact) {
                    $isChecked = (bool) $node->filterXPath('//input[@type="radio"]')
                        ->filterXPath('//input[@checked="checked"]')->count();
                    $primaryCredentialingContact[$node->text()] = $isChecked;
                });
        }

        return [
            'is_primary' => $primaryCredentialingContactLabel->count() ? $primaryCredentialingContact['Yes'] : null,
            'index' => $node->filterXPath('//input[contains(@name, "CredentialingContactVM.index")]')->attr('value'),
            'id' => $node->filterXPath('//input[contains(@name, ".Id")]')->attr('value'),
            'first_name' => $node->filterXPath('//input[contains(@name, ".FirstName")]')->attr('value'),
            'middle_name' => $node->filterXPath('//input[contains(@name, ".MiddleName")]')->attr('value'),
            'last_name' => $node->filterXPath('//input[contains(@name, ".LastName")]')->attr('value'),
            'line_1' => $node->filterXPath('//input[contains(@name, ".Street1")]')->attr('value'),
            'line_2' => $node->filterXPath('//input[contains(@name, ".Street2")]')->attr('value'),
            'city' => $node->filterXPath('//input[contains(@name, ".City")]')->attr('value'),
            'state' => $node->filterXPath('//select[contains(@name, ".StateId")]/option[contains(@selected, "selected")]')->text(''),
            'zip' => $node->filterXPath('//input[contains(@name, ".Zipcode")]')->attr('value'),
            'country' => $node->filterXPath('//select[contains(@name, ".CountryId")]/option[contains(@selected, "selected")]')->text(''),
            'province' => $node->filterXPath('//input[contains(@name, ".Province")]')->attr('value'),
            'phone_number' => $node->filterXPath('//input[contains(@name, ".PhoneNumber")]')->attr('value'),
            'fax_number' => $node->filterXPath('//input[contains(@name, ".FaxNumber")]')->attr('value'),
            'email_address' => $node->filterXPath('//input[contains(@name, ".EmailAddress")]')->attr('value'),
            'location_type' => $node->filterXPath('//select[contains(@name, ".LocationType")]/option[contains(@selected, "selected")]')->text(''),
            'location' => $node->filterXPath('//select[contains(@name, ".PracticeLocations.List")]/option[contains(@selected, "selected")]')->extract(['_text']),
            'contact_title' => $node->filterXPath('//input[contains(@name, ".ContactTitle")]')->attr('value'),
            'contact_hours_of_availability' => $node->filterXPath('//input[contains(@name, ".ContactHoursofAvailability")]')->attr('value'),
        ];
    }
}
