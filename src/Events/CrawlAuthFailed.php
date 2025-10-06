<?php

namespace TrueRcm\LaravelWebscrape\Events;

use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TrueRcm\LaravelWebscrape\Contracts\CrawlSubject;

class CrawlAuthFailed
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public CrawlSubject $subject;

    public function __construct(
        public int $subjectId
    ) {
        $this->subject = app(CrawlSubject::class)->find($this->subjectId);
    }
}
