<?php

namespace TrueRcm\LaravelWebscrape\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TrueRcm\LaravelWebscrape\Contracts\CrawlSubject;

class CrawlStarted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public CrawlSubject $subject
    ) {
    }
}
