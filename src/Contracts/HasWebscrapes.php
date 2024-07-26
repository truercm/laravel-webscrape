<?php

namespace TrueRcm\LaravelWebscrape\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
interface HasWebscrapes
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function crawlSubjects(): MorphMany;

    public function crawlUserName(): ?string;

    public function crawlPassword(): ?string;
}
