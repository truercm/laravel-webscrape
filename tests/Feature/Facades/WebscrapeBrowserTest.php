<?php

use TrueRcm\LaravelWebscrape\Browser;
use TrueRcm\LaravelWebscrape\Contracts\BrowserClient;
use TrueRcm\LaravelWebscrape\Facades\WebscrapeBrowser;

it('will handle webscrape browser facade resolution when mocked', function () {
    $browser = $this->mock(BrowserClient::class);

    $this->assertInstanceOf(BrowserClient::class, WebscrapeBrowser::getFacadeRoot());
    $this->assertSame($browser, WebscrapeBrowser::getFacadeRoot());
});

it('will handle webscrape browser facade resolution when called directly', function () {
    $this->assertInstanceOf(BrowserClient::class, WebscrapeBrowser::getFacadeRoot());
    $this->assertInstanceOf(Browser::class, WebscrapeBrowser::getFacadeRoot());
});
