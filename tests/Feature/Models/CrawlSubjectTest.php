<?php

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use TrueRcm\LaravelWebscrape\Contracts\CrawlSubject as Contract;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;
use TrueRcm\LaravelWebscrape\Models\CrawlTarget;
use TrueRcm\LaravelWebscrape\Models\CrawlTargetUrl;

beforeEach(function () {
    /*config([
        'webscrape.models.subject' => 'SubjectModel',
        'webscrape.models.target_url' => 'TargetUrlModel',
    ]);*/
});

it('will bind contract to model', function () {
    $stub = $this->app->make(Contract::class);
    $this->assertInstanceOf(CrawlSubject::class, $stub);
});

it('will relate crawlTarget to model', function () {
    $stub = new CrawlSubject();
    $this->assertInstanceOf(BelongsTo::class, $stub->crawlTarget());
    $this->assertInstanceOf(CrawlTarget::class, $stub->crawlTarget()->getModel());
});

it('will relate crawlTargetUrl to model', function () {
    $stub = new CrawlSubject();
    $this->assertInstanceOf(HasMany::class, $stub->targetUrls());
    $this->assertInstanceOf(CrawlTargetUrl::class, $stub->targetUrls()->getModel());
});

it('will relate crawlResults to model', function () {
    $stub = new CrawlSubject();
    $this->assertInstanceOf(HasMany::class, $stub->crawlResults());
    $this->assertInstanceOf(CrawlResult::class, $stub->crawlResults()->getModel());
});
