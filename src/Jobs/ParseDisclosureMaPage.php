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

class ParseDisclosureMaPage implements ShouldQueue
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
            $sections = collect([]);

            throw_if(
                0 == $this->formSections()->count() /* is not good */,
                CrawlException::parsingFailed($this->crawlResult)
            );
            $this->formSections()->each(function (Crawler $sectionNode, $i) use ($sections) {
                $questions = collect([]);
                $this->questionItems($sectionNode)->each(function (Crawler $node, $j) use ($questions) {
                    $questions->push($this->handleNode($node));
                });
                $sections->put($sectionNode->filter('p.section-title')->text(), $questions->toArray());
            });

            $this->values['form_sections'] = $sections->toArray();
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

    protected function formSections(): Crawler
    {
        return $this->crawler->filterXPath('//div[contains(@class, "form-main")]/div[contains(@class, "form-section")]');
    }

    protected function questionItems($node): Crawler
    {
        return $node->filterXPath('child::*/div[@class="row"]');
    }

    protected function handleNode($node): array
    {
        $rows = $node->filter('div.col-xs-11 > div.row');

        $question = $rows->eq(0)->filter('label.control-label')->text();

        $answers = [];

        $rows->eq(0)->filter('label.radio')->each(function ($node, $i) use (&$answers) {
            $radio = $node->filterXPath('//input[@type="radio"]')
                ->filterXPath('//input[@checked="checked"]');
            $answers[$node->text()] = $radio->count() ? true : false;
        });

        $explanation = null;

        if ($rows->count() > 0 and $rows->eq(1)->matches('.show')) {
            $explanation = $rows->eq(1)->filterXPath('//textarea[contains(@name, ".Explanation")]')->text('');
        }

        return [
            'question' => $question,
            'answers' => $answers,
            'explanation' => $explanation,
        ];
    }
}
