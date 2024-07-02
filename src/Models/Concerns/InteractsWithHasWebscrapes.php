<?php

namespace TrueRcm\LaravelWebscrape\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphOne;

trait InteractsWithHasWebscrapes
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function crawlSubject(): MorphOne
    {
        return $this->morphOne(config('webscrape.models.subject'), 'model');
    }
}
