<?php

namespace TrueRcm\LaravelWebscrape\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
interface HasWebscrapes
{
    /**
     * Relate the model to crawl subjects.
     */
    public function crawlSubjects(): MorphMany;

    /**
     * Return the credentials for the crawl subject.
     */
    public function crawlCredentials(): array;
}
