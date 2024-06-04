<?php

use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;

it('it will check all cases', function () {
    $cases = CrawlResultStatus::cases();

    $this->assertIsArray($cases);
    $this->assertCount(4, $cases);

    $names = array_column($cases, 'name');
    $values = array_column($cases, 'value');

    $this->assertSame([
        "PENDING",
        "IN_PROCESS",
        "ERROR",
        "COMPLETED",
    ], $names);

    $this->assertSame([
        "pending",
        "in_process",
        "error",
        "completed",
    ], $values);
});

it('it will check if status is completed', function (CrawlResultStatus $match, bool $expected) {
    $this->assertSame($expected, $match->isComplete());
})->with([
    'pending' => [CrawlResultStatus::PENDING, false],
    'in_progress' => [CrawlResultStatus::IN_PROCESS, false],
    'error' => [CrawlResultStatus::ERROR, false],
    'completed' => [CrawlResultStatus::COMPLETED, true],
]);
