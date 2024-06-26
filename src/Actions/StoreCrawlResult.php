<?php

namespace TrueRcm\LaravelWebscrape\Actions;

use Fls\Actions\Action;
use TrueRcm\LaravelWebscrape\Contracts\CrawlResult;

class StoreCrawlResult extends Action
{
    public function __construct(
        protected CrawlResult $crawlResult
    ) {
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'crawl_target_url_id' => ['required'],
            'crawl_subject_id' => ['required'],
            'url' => ['required'],
            'handler' => ['required'],
            'status' => ['required'],
            'body' => ['nullable'],
            'processed_at' => ['nullable', 'date'],
            'process_status' => ['nullable', 'string'],
            'result' => ['nullable', 'array'],
        ];
    }

    /**
     * @param array $attributes
     * @return \TrueRcm\LaravelWebscrape\Contracts\CrawlResult
     */
    public function handle(array $attributes): CrawlResult
    {
        $this->fill($attributes);

        return tap($this->crawlResult, function (CrawlResult $crawlResult) {
            $crawlResult
                ->forceFill($this->validated())
                ->save();
        });
    }
}
