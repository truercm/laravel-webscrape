<?php

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use TrueRcm\LaravelWebscrape\Contracts\CrawlTarget as Contract;
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
    $this->assertInstanceOf(CrawlTarget::class, $stub);
});

it('will relate CrawlSubject to model', function () {
    $stub = new CrawlTarget();
    $this->assertInstanceOf(HasOne::class, $stub->crawlSubject());
    $this->assertInstanceOf(CrawlSubject::class, $stub->crawlSubject()->getModel());
});

it('will relate crawlTargetUrl to model', function () {
    $stub = new CrawlTarget();
    $this->assertInstanceOf(HasMany::class, $stub->crawlTargetUrls());
    $this->assertInstanceOf(CrawlTargetUrl::class, $stub->crawlTargetUrls()->getModel());
});

