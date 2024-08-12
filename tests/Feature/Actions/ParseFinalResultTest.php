<?php

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Collection;
use TrueRcm\LaravelWebscrape\Actions\ParseFinalResult;
use TrueRcm\LaravelWebscrape\Enums\CrawlResultStatus;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use TrueRcm\LaravelWebscrape\Models\CrawlTargetUrl;

it('will parse final result from result pages', function () {
    CrawlTargetUrl::factory()
        ->state($items = new Sequence(
            [
                'id' => 1,
                'result_fields' => [
                    'name',
                    'gender',
                ],
            ],
            [
                'id' => 2,
                'result_fields' => [
                    'medicaid',
                    'medicare',
                ],
            ],
            [
                'id' => 3,
                'result_fields' => [
                    'university',
                    'degree',
                ],
            ],
        ))
        ->count($items->count())
        ->create();

    $crawlResults = CrawlResult::factory()
        ->state($items = new Sequence(
            [
                'id' => 1,
                'crawl_target_url_id' => 1,
                'process_status' => CrawlResultStatus::COMPLETED,
                'result' => [
                    'name' => 'Aman',
                    'gender' => 'Male',
                    'country' => 'US',
                ],
            ],
            [
                'id' => 2,
                'crawl_target_url_id' => 2,
                'process_status' => CrawlResultStatus::COMPLETED,
                'result' => [
                    'medicaid' => [11, 22],
                    'medicare' => [33, 44],
                    'licences' => [55, 66],
                ],
            ],
            [
                'id' => 3,
                'crawl_target_url_id' => 3,
                'process_status' => CrawlResultStatus::PENDING,
                'result' => [
                    'university' => 'Amsterdan',
                    'degree' => 'MD',
                ],
            ],
        ))
        ->count($items->count())
        ->create();

    $result = ParseFinalResult::run($crawlResults);
    $this->assertInstanceOf(Collection::class, $result);

    $this->assertEquals(collect([
        'name' => 'Aman',
        'gender' => 'Male',
        'medicaid' => [11, 22],
        'medicare' => [33, 44],
    ]), $result->collapse());
});
