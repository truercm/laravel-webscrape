<?php

namespace TrueRcm\LaravelWebscrape\Enums;

/**
 * @todo Convert to Enum
 * @deprecated after migrating to laravel:^9.0
 */
interface CrawlResultStatus
{
    public const PENDING = 'pending';
    public const IN_PROCESS = 'in_process';
    public const ERROR = 'error';
    public const COMPLETED = 'completed';
}
