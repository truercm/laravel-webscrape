<?php

namespace TrueRcm\LaravelWebscrape\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

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
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function crawlSubject(): MorphOne
    {
        return $this->morphOne(config('webscrape.models.subject'), 'model')
            ->latest();
    }

    /**
     * @return array
     */
    public function crawlCredentials(): array
    {
        return [];
    }
}
