<?php

use TrueRcm\LaravelWebscrape\Events\CrawlStarted;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;

it('will create new CrawlStarted event instance', function () {
    $subject = CrawlSubject::factory()->create(['id' => 111]);

    $event = new CrawlStarted($subject);

    $this->assertSame($subject, $event->subject);
});
