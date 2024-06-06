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

class ParseEducationAndProfessionalTrainingPage implements ShouldQueue
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
            $this->educationNodes()->each(function (Crawler $node, $i) use ($values) {
                $values->push($this->handleEducation($node));
            });
            $this->values['educations'] = $values->toArray();

            $values = collect([]);
            $this->trainingNodes()->each(function (Crawler $node, $i) use ($values) {
                $values->push($this->handleTraining($node));
            });
            $this->values['trainings'] = $values->toArray();


            $this->values['Completed cultural competency training'] = (bool) $this->crawler
                ->filter('input#ProviderProfessionalDetails_isculturalcompetencytrainingcompleted')
                ->reduce(function ($node, $i) {
                    return $node->attr('checked') == 'checked' and $node->attr('value') == '100000000';
                })
                ->count();
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

    protected function educationNodes(): Crawler
    {
        return $this->crawler->filterXPath('//div[@class="edu-main"]/div/div[contains(@id, "SummaryPageGridEditRecord")]');
    }
    protected function trainingNodes(): Crawler
    {
        return $this->crawler->filterXPath('//div[@class="profTraining-main"]/div/div[contains(@id, "SummaryPageGridEditRecord")]');
    }

    protected function handleEducation($node): array
    {
        $colNodes = $node->filter('div.grid-inner');
        return [
            'degree' => $colNodes->eq(0)->text(),
            'institution' => collect($colNodes->eq(1)->filter('p')->extract(['_text']))
                ->map(fn ($string) => trim(preg_replace("/(?:[ \n\r\t\x0C]{2,}+|[\n\r\t\x0C])/", ' ', $string), " \n\r\t\x0C"))
                ->filter()
                ->implode(PHP_EOL),
        ];
    }

    protected function handleTraining($node): array
    {
        $colNodes = $node->filter('div.grid-inner');
        return [

        ];
    }
}
