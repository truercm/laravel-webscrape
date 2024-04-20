<?php

namespace TrueRcm\LaravelWebscrape\Actions;

use TrueRcm\LaravelWebscrape\Models\Contracts\CrawlSubject;
use TrueRcm\LaravelWebscrape\Models\Contracts\CrawlResult;
use TrueRcm\LaravelWebscrape\Models\Contracts\CrawlTargetUrl;
use Fls\Actions\Action;

class AddCrawlResult extends Action
{
    /**
     * @param \TrueRcm\LaravelWebscrape\Models\Contracts\CrawlSubject $subject
     * @param \TrueRcm\LaravelWebscrape\Models\Contracts\CrawlTargetUrl $target
     * @param array $attributes
     * @return \TrueRcm\LaravelWebscrape\Models\Contracts\CrawlResult
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
