<?php

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TrueRcm\LaravelWebscrape\Contracts\CrawlTargetUrl as Contract;
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
    $this->assertInstanceOf(CrawlTargetUrl::class, $stub);
});

it('will relate crawlTarget to model', function () {
    $stub = new CrawlTargetUrl();
    $this->assertInstanceOf(BelongsTo::class, $stub->crawlTarget());
    $this->assertInstanceOf(CrawlTarget::class, $stub->crawlTarget()->getModel());
});
