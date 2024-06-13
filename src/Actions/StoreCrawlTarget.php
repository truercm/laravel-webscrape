<?php

namespace TrueRcm\LaravelWebscrape\Actions;

use Fls\Actions\Action;
use TrueRcm\LaravelWebscrape\Contracts\CrawlTarget;

class StoreCrawlTarget extends Action
{
    public function __construct(
        protected CrawlTarget $target
    ) {
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'url' => 'required|url|unique:crawl_targets,url',
            'name' => 'required|string|max:255',
            'auth_url' => 'required|url',
            'auth_button_text' => 'required|string|max:255',
            'crawling_job' => 'string|max:255',
        ];
    }

    /**
     * Handle registering a new crawl target.
     *
     * @param array $attributes
     * @return \TrueRcm\LaravelWebscrape\Contracts\CrawlTarget
     */
    public function handle(array $attributes): CrawlTarget
    {
        $this->fill($attributes);

        return tap($this->target, function (CrawlTarget $crawlTarget) {
            $crawlTarget
                ->forceFill($this->validated())
                ->save();
        });
    }
}
