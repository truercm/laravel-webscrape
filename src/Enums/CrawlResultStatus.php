<?php

namespace TrueRcm\LaravelWebscrape\Enums;

//TODO: Implement php enum
Interface CrawlResultStatus
{
    public const PENDING = 'pending';
    public const IN_PROCESS = 'in_process';
    public const ERROR = 'error';
    public const COMPLETED = 'completed';
}
