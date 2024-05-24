<?php

namespace TrueRcm\LaravelWebscrape\Actions;

use Fls\Actions\Action;
use TrueRcm\LaravelWebscrape\Contracts\CrawlResult;

class UpdateCrawlResult extends Action
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'crawl_target_url_id' => ['sometimes', 'required'],
            'crawl_subject_id' => ['sometimes', 'required'],
            'url' => ['sometimes', 'required'],
            'handler' => ['sometimes', 'required'],
            'status' => ['sometimes', 'required'],
            'body' => ['nullable'],
            'processed_at' => ['nullable', 'date'],
            'process_status' => ['nullable', 'string'],
            'result' => ['nullable', 'array'],
        ];
    }

    /**
     * @param \TrueRcm\LaravelWebscrape\Contracts\CrawlResult $crawlResult
     * @param array $attributes
     * @return \TrueRcm\LaravelWebscrape\Contracts\CrawlResult
     */
    public function handle(CrawlResult $crawlResult, array $attributes): CrawlResult
    {
        $this->fill($attributes);

        $this->validate();
        if($this->get('result') !== null){
            $this->set('result', json_encode($this->get('result')));
        }

        return tap($crawlResult, function (CrawlResult $crawlResult) {
            $crawlResult
                ->forceFill($this->all())
                ->save();
        });
    }
}
