<?php

namespace TrueRcm\LaravelWebscrape\Actions;

use Fls\Actions\Action;
use TrueRcm\LaravelWebscrape\Contracts\CrawlSubject;

class UpdateCrawlSubject extends Action
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'credentials' => ['sometimes', 'required', 'array'],
            'authenticated_at' => ['nullable', 'date'],
            'result' => ['nullable', 'array'],
        ];
    }

    /**
     * @param \TrueRcm\LaravelWebscrape\Contracts\CrawlSubject $crawlSubject
     * @param array $attributes
     * @return \TrueRcm\LaravelWebscrape\Contracts\CrawlSubject
     */
    public function handle(CrawlSubject $crawlSubject, array $attributes): CrawlSubject
    {
        $this->fill($attributes);

        return tap($crawlSubject, function (CrawlSubject $crawlSubject) {
            $crawlSubject
                ->forceFill($this->validated())
                ->save();
        });
    }
}
