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

class ParseDocumentsPage implements ShouldQueue
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
        $this->crawler =  new Crawler($this->crawlResult->body, $this->crawlResult->url);

        try {
            $documents = collect([]);

            throw_if(
                0 == $this->nodes()->count() /* is not good */,
                CrawlException::parsingFailed($this->crawlResult)
            );
            $this->nodes()->each(function (Crawler $node, $i) use($documents){
                if ($i < $this->nodes()->count() - 1) {
                    $documents->push($this->handleNode($node));
                }
            });

            $this->values['documents'] = $documents->toArray();
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
        return $this->crawler->filter('div.e-gridcontent tr');
    }

    protected function handleNode($node): array
    {
        $colNodes = $node->filter('td');

        return [
            'name' => $colNodes->eq(0)->filter('a')->text(),
            'link' => $colNodes->eq(0)->filter('a')->link()->getUri(),
            'state' => $colNodes->eq(1)->filter('span')->text(''),
            'uploaded_date' => $colNodes->eq(2)->filter('span')->text(''),
            'expiration_date' => $colNodes->eq(3)->filter('span')->text(''),
            'status' => $colNodes->eq(4)->filter('span')->text(''),
        ];
    }
}
