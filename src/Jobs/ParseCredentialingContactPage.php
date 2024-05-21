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

class ParseCredentialingContactPage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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
        $this->crawlResult->forceFill([
            'process_status' => CrawlResultStatus::COMPLETED,
        ]);
        $result = [];
        $crawler = new Crawler($this->crawlResult->body, $this->crawlResult->url);

        try {
            $credentialingContacts = [];
            $items = $crawler->filter('div#CredentialingContactPlaceHolder div.collection-item');

            $items->each(function ($node, $i) use (&$credentialingContacts) {
                $temp = [];
                $temp['index'] = $node->filterXPath('//input[contains(@name, "CredentialingContactVM.index")]')->attr('value');
                $temp['id'] = $node->filterXPath('//input[contains(@name, ".Id")]')->attr('value');
                $temp['first_name'] = $node->filterXPath('//input[contains(@name, ".FirstName")]')->attr('value');
                $temp['middle_name'] = $node->filterXPath('//input[contains(@name, ".MiddleName")]')->attr('value');
                $temp['last_name'] = $node->filterXPath('//input[contains(@name, ".LastName")]')->attr('value');
                $temp['line_1'] = $node->filterXPath('//input[contains(@name, ".Street1")]')->attr('value');
                $temp['line_2'] = $node->filterXPath('//input[contains(@name, ".Street2")]')->attr('value');
                $temp['city'] = $node->filterXPath('//input[contains(@name, ".City")]')->attr('value');
                $temp['state'] = $node->filterXPath('//select[contains(@name, ".StateId")]/option[contains(@selected, "selected")]')->text('');
                $temp['zip'] = $node->filterXPath('//input[contains(@name, ".Zipcode")]')->attr('value');
                $temp['country'] = $node->filterXPath('//select[contains(@name, ".CountryId")]/option[contains(@selected, "selected")]')->text('');
                $temp['province'] = $node->filterXPath('//input[contains(@name, ".Province")]')->attr('value');
                $temp['phone_number'] = $node->filterXPath('//input[contains(@name, ".PhoneNumber")]')->attr('value');
                $temp['fax_number'] = $node->filterXPath('//input[contains(@name, ".FaxNumber")]')->attr('value');
                $temp['email_address'] = $node->filterXPath('//input[contains(@name, ".EmailAddress")]')->attr('value');

                $primaryCredentialingContact = [];
                $inputs = $node->filterXPath('//label[contains(@for, "PrimaryCredentialingContact")]')
                    ->siblings()
                    ->filter('label.radio');

                $inputs->each(function ($node, $i) use (&$primaryCredentialingContact) {
                    $radio = $node->filterXPath('//input[@type="radio"]')
                        ->filterXPath('//input[@checked="checked"]');
                    $primaryCredentialingContact[$node->text()] = $radio->count() ? true : false;
                });

                $temp['is_primary'] = $primaryCredentialingContact['Yes'];

                $temp['location_type'] = $node->filterXPath('//select[contains(@name, ".LocationType")]/option[contains(@selected, "selected")]')->text('');
                $temp['location'] = $node->filterXPath('//select[contains(@name, ".PracticeLocations.List")]/option[contains(@selected, "selected")]')->extract(['_text']);

                $temp['contact_title'] = $node->filterXPath('//input[contains(@name, ".ContactTitle")]')->attr('value');
                $temp['contact_hours_of_availability'] = $node->filterXPath('//input[contains(@name, ".ContactHoursofAvailability")]')->attr('value');

                $credentialingContacts[] = $temp;
            });

            $result['credentialing_contacts'] = $credentialingContacts;
        } catch (\Exception $e) {
            $error = __('Error :message at line :line', ['message' => $e->getMessage(), 'line' => $e->getLine()]);
            $result['error'] = $error;
            $this->crawlResult->forceFill([
                'process_status' => CrawlResultStatus::ERROR,
            ]);
        }

        $this->crawlResult->forceFill([
            'processed_at' => now(),
            'result' => $result,
        ])->save();
    }
}
