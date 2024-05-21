<?php

use TrueRcm\LaravelWebscrape\Services\TextExtractor;
use TrueRcm\LaravelWebscrape\Services\TextExtractorService;

it('will handle text-extractor facade resolution', function () {
    $this->assertEquals('text-extractor', TextExtractor::getFacadeAccessor());
    $this->assertInstanceOf(TextExtractorService::class, TextExtractor::getFacadeRoot());
    $this->assertInstanceOf(TextExtractorService::class, app('text-extractor'));
});
