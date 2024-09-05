<?php

namespace TrueRcm\LaravelWebscrape\Actions;

use Fls\Actions\Action;
use TrueRcm\LaravelWebscrape\Contracts\CrawlResult;
use TrueRcm\LaravelWebscrape\Contracts\CrawlSubject;
use TrueRcm\LaravelWebscrape\Contracts\CrawlTargetUrl;

class AddCrawlResult extends Action
{
    /**
     * @param \TrueRcm\LaravelWebscrape\Contracts\CrawlSubject $subject
     * @param \TrueRcm\LaravelWebscrape\Contracts\CrawlTargetUrl $target
     * @param array <string, mixed>$attributes
     * @return \TrueRcm\LaravelWebscrape\Contracts\CrawlResult
     */
    public function handle(CrawlSubject $subject, CrawlTargetUrl $target, array $attributes): CrawlResult
    {
        $this
            ->fill($attributes)
            ->fill([
                'crawl_subject_id' => $subject->getKey(),
                'crawl_target_url_id' => $target->getKey(),
            ]);

        return StoreCrawlResult::run($this->all());
    }
}
