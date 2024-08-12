<?php

namespace TrueRcm\LaravelWebscrape\Enums;


class CrawlResultStatus
{
    public const PENDING = 'pending';
    public const IN_PROCESS = 'in_process';
    public const ERROR = 'error';
    public const COMPLETED = 'completed';
}
