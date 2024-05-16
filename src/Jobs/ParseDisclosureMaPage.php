<?php

namespace TrueRcm\LaravelWebscrape\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;


class ParseDisclosureMaPage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected CrawlResult $crawlResult
    )
    {
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
        $sections = [];

        try{
            $formSections = $crawler->filterXPath('//div[contains(@class, "form-main")]/div[contains(@class, "form-section")]');

            $formSections->each(function ($formSection, $i) use(&$sections) {
                $temp = [];
                $temp['section'] = $formSection->filter('p.section-title')->text();
                $questions = [];
                $questionItems = $formSection->filterXPath('child::*/div[@class="row"]');

                $questionItems->each(function ($node, $i) use (&$questions) {
                    $rows = $node->filter('div.col-xs-11 > div.row');

                    $question = $rows->eq(0)->filter('label.control-label')->text();

                    $answers = [];

                    $rows->eq(0)->filter('label.radio')->each(function ($node, $i) use (&$answers) {
                        $radio = $node->filterXPath('//input[@type="radio"]')
                            ->filterXPath('//input[@checked="checked"]');
                        $answers[$node->text()] = $radio->count() ? true : false;
                    });
                    $explanation = null;
                    if ($rows->eq(1)->matches('.show')) {
                        $explanation = $rows->eq(1)->filterXPath('//textarea[contains(@name, ".Explanation")]')->text('');
                    }
                    $questions[] = [
                        'question' => $question,
                        'answers' => $answers,
                        'explanation' => $explanation,
                    ];

                });

                $temp['questions'] = $questions;

                $sections[] = $temp;

            });
        }catch(\Exception $e){
            $error = __("Error :message at line :line", ['message' => $e->getMessage(), 'line' => $e->getLine()]);
            $result['error'] = $error;
            $this->crawlResult->forceFill([
                'process_status' => CrawlResultStatus::ERROR,
            ]);
        }

        $result[] = $sections;

        $this->crawlResult->forceFill([
            'processed_at' => now(),
            'result' => $result
        ])->save();

    }
}
