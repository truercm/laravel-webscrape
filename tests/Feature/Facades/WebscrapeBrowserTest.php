<?php

use TrueRcm\LaravelWebscrape\Contracts\BrowserClient;
use TrueRcm\LaravelWebscrape\Facades\WebscrapeBrowser;

it('will handle webscrape browser facade resolution', function () {
    $browser = $this->mock(BrowserClient::class);
    $this->assertInstanceOf(BrowserClient::class, WebscrapeBrowser::getFacadeRoot());
    $this->assertSame($browser, WebscrapeBrowser::getFacadeRoot());
});
