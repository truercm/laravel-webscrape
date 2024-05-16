<?php

it('can create a crawl traveller', function () {
    $subject = \TrueRcm\LaravelWebscrape\Models\CrawlSubject::factory()->create();
    dd($subject);
});
