<?php

namespace TrueRcm\LaravelWebscrape\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
interface HasWebscrapes
{
    /**
     * Model has Cancellation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function crawlSubject(): MorphOne;
}
