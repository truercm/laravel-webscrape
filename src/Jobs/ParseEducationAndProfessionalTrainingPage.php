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

class ParseEducationAndProfessionalTrainingPage implements ShouldQueue
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
            $educations = [];
            $records = $crawler->filterXPath('//div[@class="edu-main"]/div/div[contains(@id, "SummaryPageGridEditRecord")]');
            $records->each(function ($node, $i) use (&$educations) {
                $temp = [];
                $colNodes = $node->filter('div.grid-inner');
                $temp['degree'] = $colNodes->eq(0)->text();
                $temp['institution'] = collect($colNodes->eq(1)->filter('p')->extract(['_text']))
                    ->map(fn ($string) => trim(preg_replace("/(?:[ \n\r\t\x0C]{2,}+|[\n\r\t\x0C])/", ' ', $string), " \n\r\t\x0C"))
                    ->filter()
                    ->implode(PHP_EOL);
                $educations[] = $temp;
            });

            $result['educations'] = $educations;

            $professionalTrainings = [];
            $records = $crawler->filterXPath('//div[@class="profTraining-main"]/div/div[contains(@id, "SummaryPageGridEditRecord")]');
            $records->each(function ($node, $i) use (&$educations) {
                $temp = [];
                $colNodes = $node->filter('div.grid-inner');
                $temp['degree'] = $colNodes->eq(0)->text();
                $temp['university'] = collect($colNodes->eq(1)->filter('p')->extract(['_text']))
                    ->map(fn ($string) => trim(preg_replace("/(?:[ \n\r\t\x0C]{2,}+|[\n\r\t\x0C])/", ' ', $string), " \n\r\t\x0C"))
                    ->filter()
                    ->implode(PHP_EOL);
                $professionalTrainings[] = $temp;
            });

            $result['trainings'] = $professionalTrainings;

            $result['Completed cultural competency training'] = $crawler->filter('div.profTraining-main div.custom-radio-dev label.font-bold')->text();
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
