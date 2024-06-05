<?php

use TrueRcm\LaravelWebscrape\Contracts\BrowserClient;
use TrueRcm\LaravelWebscrape\Facades\WebscrapeBrowser;

it('will handle webscrape browser facade resolution', function () {
    $browser = $this->mock(BrowserClient::class);
    $this->assertEquals(BrowserClient::class, WebscrapeBrowser::getFacadeAccessor());
    $this->assertInstanceOf(BrowserClient::class, WebscrapeBrowser::getFacadeRoot());
    $this->assertInstanceOf(BrowserClient::class, app(BrowserClient::class));
    $this->assertSame($browser, app(BrowserClient::class));
});
