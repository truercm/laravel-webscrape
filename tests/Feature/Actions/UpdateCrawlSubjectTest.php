<?php

use TrueRcm\LaravelWebscrape\Actions\UpdateCrawlSubject;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;

it('will update a crawl subject', function () {
    $subject = CrawlSubject::factory()->create(['id' => 111]);

    $attributes = [
        'credentials' => ['user' => 'xear', 'password' => '12345'],
        'authenticated_at' => 'November 9, 2022 6:00 PM',
        'result' => ['name' => 'Amit'],
    ];

    $result = UpdateCrawlSubject::run($subject, $attributes);

    $this->assertInstanceOf(CrawlSubject::class, $result);
    $this->assertTrue($subject->is($result));
    $this->assertDatabaseCount('crawl_subjects', 1);
    $this->assertDatabaseHas('crawl_subjects', [
        'credentials' => json_encode(['user' => 'xear', 'password' => '12345']),
        'authenticated_at' => 'November 9, 2022 6:00 PM',
        'result' => json_encode(['name' => 'Amit']),
    ]);
});

it('will return consistent validation rule', function () {
    $stub = UpdateCrawlSubject::make();

    $this->assertEquals([
        'credentials' => ['sometimes', 'required', 'array'],
        'authenticated_at' => ['nullable', 'date'],
        'result' => ['nullable', 'array'],
    ], $stub->rules());
});
