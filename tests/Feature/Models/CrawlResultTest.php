<?php

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TrueRcm\LaravelWebscrape\Contracts\CrawlResult as Contract;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;
use TrueRcm\LaravelWebscrape\Models\CrawlTargetUrl;

beforeEach(function () {
    /*config([
        'webscrape.models.subject' => 'SubjectModel',
        'webscrape.models.target_url' => 'TargetUrlModel',
    ]);*/
});

it('will bind contract to model', function () {
    $stub = $this->app->make(Contract::class);
    $this->assertInstanceOf(CrawlResult::class, $stub);
});

it('will relate crawlSubject to model', function () {
    $stub = new CrawlResult();
    $this->assertInstanceOf(BelongsTo::class, $stub->crawlSubject());
    $this->assertInstanceOf(CrawlSubject::class, $stub->crawlSubject()->getModel());
});

it('will relate crawlTargetUrl to model', function () {
    $stub = new CrawlResult();
    $this->assertInstanceOf(BelongsTo::class, $stub->crawlTargetUrl());
    $this->assertInstanceOf(CrawlTargetUrl::class, $stub->crawlTargetUrl()->getModel());
});
