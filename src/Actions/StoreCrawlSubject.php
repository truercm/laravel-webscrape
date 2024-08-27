<?php

namespace TrueRcm\LaravelWebscrape\Actions;

use Fls\Actions\Action;
use TrueRcm\LaravelWebscrape\Contracts\CrawlSubject;

class StoreCrawlSubject extends Action
{
    public function __construct(
        protected CrawlSubject $crawlSubject
    ) {
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'model_type' => ['required', 'string'],
            'model_id' => ['required', 'numeric'],
            'crawl_target_id' => ['required', 'numeric'],
            'credentials' => ['required', 'array'],
            'authenticated_at' => ['nullable', 'date'],
            'result' => ['nullable', 'array'],
        ];
    }

    /**
     * @param array $attributes
     * @return \TrueRcm\LaravelWebscrape\Contracts\CrawlSubject
     */
    public function handle(array $attributes): CrawlSubject
    {
        $this->fill($attributes);

        return tap($this->crawlSubject, function (CrawlSubject $crawlSubject) {
            $crawlSubject
                ->forceFill($this->validated())
                ->save();
        });
    }
}
