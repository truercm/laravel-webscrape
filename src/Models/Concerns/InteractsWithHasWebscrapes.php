<?php

namespace TrueRcm\LaravelWebscrape\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;

trait InteractsWithHasWebscrapes
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function crawlSubjects(): MorphMany
    {
        return $this->morphMany(config('webscrape.models.subject'), 'model');
    }

    /**
     * @return string|null
     */
    public function crawlUserName(): ?string
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function crawlPassword(): ?string
    {
        return null;
    }
}
