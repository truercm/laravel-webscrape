<?php

use TrueRcm\LaravelWebscrape\Events\CrawlFailed;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;

it('will create new CrawlFailed event instance', function () {
    $subject = CrawlSubject::factory()->create(['id' => 111]);

    $event = new CrawlFailed($subject);

    $this->assertSame($subject, $event->subject);
});
