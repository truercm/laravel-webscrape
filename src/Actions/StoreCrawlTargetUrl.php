<?php

namespace TrueRcm\LaravelWebscrape\Actions;

use Fls\Actions\Action;
use TrueRcm\LaravelWebscrape\Contracts\CrawlTargetUrl;

class StoreCrawlTargetUrl extends Action
{
    public function __construct(
        protected CrawlTargetUrl $targetUrl
    ) {
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'crawl_target_id' => 'required|numeric',
            'url_template' => 'required|url|unique:crawl_targets,url',
            'handler' => 'required|string|max:255',
            'result_fields' => 'nullable|array',
        ];
    }

    /**
     * Handle registering a new crawl target url.
     *
     * @param array $attributes
     * @return \TrueRcm\LaravelWebscrape\Contracts\CrawlTargetUrl
     */
    public function handle(array $attributes): CrawlTargetUrl
    {
        $this->fill($attributes);

        return tap($this->targetUrl, function (CrawlTargetUrl $crawlTargetUrl) {
            $crawlTargetUrl
                ->forceFill($this->validated())
                ->save();
        });
    }
}
