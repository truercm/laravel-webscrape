<?php

use TrueRcm\LaravelWebscrape\Events\CrawlCompleted;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;

it('will create new CrawlCompleted event instance', function () {
    $subject = CrawlSubject::factory()->create(['id' => 111]);

    $event = new CrawlCompleted($subject);

    $this->assertSame($subject, $event->subject);
});
