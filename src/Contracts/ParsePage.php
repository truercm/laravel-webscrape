<?php

namespace TrueRcm\LaravelWebscrape\Contracts;

interface ParsePage
{
    /**
     * Handle the page parsing.
     */
    public function handle(): void;
}
