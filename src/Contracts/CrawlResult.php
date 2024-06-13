<?php

namespace TrueRcm\LaravelWebscrape\Contracts;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 * @property string $handler
 * @property \TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus $process_status
 */
interface CrawlResult
{
    /**
     * Defer to the status to identify if the status is final.
     *
     * @return bool
     */
    public function isComplete(): bool;
}
