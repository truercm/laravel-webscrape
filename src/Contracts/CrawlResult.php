<?php

namespace TrueRcm\LaravelWebscrape\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 * @property string $handler
 * @property \TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus $process_status
 */
interface CrawlResult
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function crawlSubject(): BelongsTo;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function crawlTargetUrl(): BelongsTo;

    /**
     * Defer to the status to identify if the status is final.
     *
     * @return bool
     */
    public function isComplete(): bool;
}
