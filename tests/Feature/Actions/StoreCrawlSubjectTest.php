<?php

use TrueRcm\LaravelWebscrape\Actions\StoreCrawlSubject;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;

it('will persist a crawl subject', function () {
    $attributes = [
        'model_type' => 'App\Models\User',
        'model_id' => 11,
        'crawl_target_id' => 22,
        'credentials' => ['user' => 'xear', 'password' => '12345'],
        'authenticated_at' => 'November 9, 2022 6:00 PM',
        'result' => ['name' => 'Amit'],
    ];

    $result = StoreCrawlSubject::run($attributes);

    $this->assertInstanceOf(CrawlSubject::class, $result);
    $this->assertDatabaseCount('crawl_subjects', 1);
    $this->assertDatabaseHas('crawl_subjects', [
        'model_type' => 'App\Models\User',
        'model_id' => 11,
        'crawl_target_id' => 22,
        'credentials' => json_encode(['user' => 'xear', 'password' => '12345']),
        'authenticated_at' => '2022-11-09 18:00:00',
        'result' => json_encode(['name' => 'Amit']),
    ]);
});

it('will return consistent validation rule', function () {
    $stub = StoreCrawlSubject::make();

    $this->assertEquals([
        'model_type' => ['required', 'string'],
        'model_id' => ['required', 'numeric'],
        'crawl_target_id' => ['required', 'numeric'],
        'credentials' => ['required', 'array'],
        'authenticated_at' => ['nullable', 'date'],
        'result' => ['nullable', 'array'],
    ], $stub->rules());
});
