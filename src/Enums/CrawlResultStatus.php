<?php

namespace TrueRcm\LaravelWebscrape\Enums;

enum CrawlResultStatus: string
{
    case PENDING = 'pending';
    case IN_PROCESS = 'in_process';
    case ERROR = 'error';
    case COMPLETED = 'completed';
    case YOUTUBE = 'youtube';

    public function isComplete(): bool
    {
        return self::COMPLETED === $this;
    }
}
